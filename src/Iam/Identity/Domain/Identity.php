<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityConfirmationRequested;
use Iam\Identity\Domain\Event\IdentityConfirmed;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\Specification\PendingIdentityExpiredSpecification;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\IdentityModerationState;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Identity\Domain\ValueObject\IdentityVerificationState;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\Service\CooldownCalculator;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\CooldownElapsedSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\VerificationCodeKey;

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

    #[Id]
    public private(set) IdentityId $id;
    private IdentityVerificationState $verificationState;
    private IdentityModerationState $moderationState;
    private ErasureState $erasureState;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $confirmationRequestedAt;

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
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws InvalidConfirmationCodeException
     */
    public function confirm(#[\SensitiveParameter] string $code, CodeChallengerInterface $codeChallenger, \DateTimeImmutable $confirmedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->verificationState->isConfirmed()) {
            return;
        }

        if (!$codeChallenger->verify(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $this->id->toString()), $code, $confirmedAt)) {
            throw InvalidConfirmationCodeException::forId($this->id);
        }

        $this->recordThat(new IdentityConfirmed(
            id: $this->id,
            confirmedAt: $confirmedAt,
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

        if ($this->moderationState->isSuspended()) {
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
     */
    public function reactivate(Reason $reason, \DateTimeImmutable $reactivatedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->moderationState->isActive()) {
            return;
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id,
            reason: $reason,
            reactivatedAt: $reactivatedAt,
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     * @throws IdentityAlreadyConfirmedException
     * @throws ConfirmationRequestedTooRecentlyException
     */
    public function requestConfirmation(\DateTimeImmutable $requestedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->verificationState->isConfirmed()) {
            throw IdentityAlreadyConfirmedException::forId($this->id);
        }

        $cooldownCalculator = new CooldownCalculator();
        if (!new CooldownElapsedSpecification($cooldownCalculator, $requestedAt)->isSatisfiedBy($this->confirmationRequestedAt)) {
            throw ConfirmationRequestedTooRecentlyException::forId($this->id, $cooldownCalculator->retryAt($this->confirmationRequestedAt));
        }

        $this->recordThat(new IdentityConfirmationRequested(
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

    public function erasePending(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->verificationState->isPending()) {
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

    #[Apply]
    private function applyRegistered(IdentityRegistered $event): void
    {
        $this->id = $event->id;
        $this->verificationState = IdentityVerificationState::PENDING;
        $this->moderationState = IdentityModerationState::ACTIVE;
        $this->erasureState = ErasureState::RETAINED;
        $this->registeredAt = $event->registeredAt;
        $this->confirmationRequestedAt = $event->registeredAt;
    }

    #[Apply]
    private function applyConfirmed(IdentityConfirmed $event): void
    {
        $this->verificationState = IdentityVerificationState::CONFIRMED;
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
        $this->moderationState = IdentityModerationState::SUSPENDED;
    }

    #[Apply]
    private function applyReactivated(IdentityReactivated $event): void
    {
        $this->moderationState = IdentityModerationState::ACTIVE;
    }

    #[Apply]
    private function applyConfirmationRequested(IdentityConfirmationRequested $event): void
    {
        $this->confirmationRequestedAt = $event->requestedAt;
    }
}
