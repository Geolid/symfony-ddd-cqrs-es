<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityActivated;
use Iam\Identity\Domain\Event\IdentityEmailConfirmationResendRequested;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\EmailConfirmationResendRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\Specification\PendingIdentityExpiredSpecification;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\VerificationCodeInterface;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\CooldownElapsedSpecification;
use Shared\Domain\ValueObject\ErasureState;

#[Aggregate('iam.identity.identity')]
final class Identity implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::REQUESTED],
        ErasureState::REQUESTED->value => [ErasureState::RETAINED, ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    /** @var array<string, list<IdentityState>> */
    private const array ACCESS_TRANSITIONS = [
        IdentityState::PENDING->value => [IdentityState::ACTIVE, IdentityState::SUSPENDED],
        IdentityState::ACTIVE->value => [IdentityState::SUSPENDED],
        IdentityState::SUSPENDED->value => [IdentityState::ACTIVE],
    ];

    private const string EMAIL_CONFIRMATION_RESEND_COOLDOWN = '+60 seconds';

    #[Id]
    public private(set) IdentityId $id;
    private IdentityState $accessState;
    private ErasureState $erasureState;
    private \DateTimeImmutable $registeredAt;
    private ?\DateTimeImmutable $emailConfirmationResendRequestedAt = null;

    public static function register(IdentityId $id, FullName $fullName, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new IdentityRegistered(
            id: $id,
            fullName: $fullName,
            email: $email,
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    /**
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotPendingException
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws InvalidConfirmationCodeException
     */
    public function activate(#[\SensitiveParameter] string $code, VerificationCodeInterface $verificationCode, \DateTimeImmutable $activatedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->accessState->isActive()) {
            return;
        }

        if ($this->accessState->isSuspended()) {
            throw IdentityNotPendingException::forId($this->id);
        }

        if (!$verificationCode->verify(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $this->id->toString(), $code, $activatedAt)) {
            throw InvalidConfirmationCodeException::forId($this->id);
        }

        $this->recordThat(new IdentityActivated(
            id: $this->id,
            activatedAt: $activatedAt,
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     */
    public function suspend(Reason $reason, \DateTimeImmutable $suspendedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if (!$this->canTransitionAccessTo(IdentityState::SUSPENDED)) {
            return;
        }

        $this->recordThat(new IdentitySuspended(
            id: $this->id,
            reason: $reason,
            suspendedAt: $suspendedAt,
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotSuspendedException
     */
    public function reactivate(Reason $reason, \DateTimeImmutable $reactivatedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->accessState->isActive()) {
            return;
        }

        if ($this->accessState->isPending()) {
            throw IdentityNotSuspendedException::forId($this->id);
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id,
            reason: $reason,
            reactivatedAt: $reactivatedAt,
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotPendingException
     * @throws EmailConfirmationResendRequestedTooRecentlyException
     */
    public function requestEmailConfirmationResend(\DateTimeImmutable $requestedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if (!$this->accessState->isPending()) {
            throw IdentityNotPendingException::forId($this->id);
        }

        if (!new CooldownElapsedSpecification(self::EMAIL_CONFIRMATION_RESEND_COOLDOWN, $requestedAt)->isSatisfiedBy($this->emailConfirmationResendRequestedAt)) {
            throw EmailConfirmationResendRequestedTooRecentlyException::forId($this->id);
        }

        $this->recordThat(new IdentityEmailConfirmationResendRequested(
            id: $this->id,
            requestedAt: $requestedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::REQUESTED)) {
            return;
        }

        $this->recordThat(new IdentityErasureRequested(
            id: $this->id,
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::RETAINED)) {
            return;
        }

        $this->recordThat(new IdentityErasureCancelled(
            id: $this->id,
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::ERASED)) {
            return;
        }

        $this->recordThat(new IdentityErased(
            id: $this->id,
            erasedAt: $erasedAt,
        ));
    }

    public function eraseUnconfirmed(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->accessState->isPending()) {
            return;
        }

        if (!new PendingIdentityExpiredSpecification($erasedAt)->isSatisfiedBy($this->registeredAt)) {
            return;
        }

        if (!$this->erasureState->isRetained()) {
            return;
        }

        $this->recordThat(new IdentityErased(
            id: $this->id,
            erasedAt: $erasedAt,
        ));
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    private function canTransitionAccessTo(IdentityState $target): bool
    {
        return new CanTransitionToSpecification(self::ACCESS_TRANSITIONS, $target)->isSatisfiedBy($this->accessState);
    }

    #[Apply]
    private function applyRegistered(IdentityRegistered $event): void
    {
        $this->id = $event->id;
        $this->accessState = IdentityState::PENDING;
        $this->erasureState = ErasureState::RETAINED;
        $this->registeredAt = $event->registeredAt;
    }

    #[Apply]
    private function applyActivated(IdentityActivated $event): void
    {
        $this->accessState = IdentityState::ACTIVE;
    }

    #[Apply]
    private function applyErasureRequested(IdentityErasureRequested $event): void
    {
        $this->erasureState = ErasureState::REQUESTED;
    }

    #[Apply]
    private function applyErasureCancelled(IdentityErasureCancelled $event): void
    {
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyErased(IdentityErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }

    #[Apply]
    private function applySuspended(IdentitySuspended $event): void
    {
        $this->accessState = IdentityState::SUSPENDED;
    }

    #[Apply]
    private function applyReactivated(IdentityReactivated $event): void
    {
        $this->accessState = IdentityState::ACTIVE;
    }

    #[Apply]
    private function applyEmailConfirmationResendRequested(IdentityEmailConfirmationResendRequested $event): void
    {
        $this->emailConfirmationResendRequestedAt = $event->requestedAt;
    }
}
