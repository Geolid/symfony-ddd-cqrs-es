<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\ErasureState;

#[Aggregate('iam.identity.identity')]
final class Identity implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::PENDING],
        ErasureState::PENDING->value => [ErasureState::RETAINED, ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) IdentityId $id;
    private IdentityState $accessState;
    private ErasureState $erasureState;

    public static function register(IdentityId $id, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new IdentityRegistered(
            id: $id->toString(),
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    /**
     * @throws IdentityAlreadyErasedException
     */
    public function suspend(Reason $reason, \DateTimeImmutable $suspendedAt): void
    {
        if ($this->erasureState->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->accessState->isSuspended()) {
            return;
        }

        $this->recordThat(new IdentitySuspended(
            id: $this->id->toString(),
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

        if ($this->accessState->isActive()) {
            return;
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id->toString(),
            reason: $reason,
            reactivatedAt: $reactivatedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::PENDING)) {
            return;
        }

        $this->recordThat(new IdentityErasureRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::RETAINED)) {
            return;
        }

        $this->recordThat(new IdentityErasureCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::ERASED)) {
            return;
        }

        $this->recordThat(new IdentityErased(
            id: $this->id->toString(),
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
        $this->id = IdentityId::fromString($event->id);
        $this->accessState = IdentityState::ACTIVE;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyErasureRequested(IdentityErasureRequested $event): void
    {
        $this->erasureState = ErasureState::PENDING;
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
}
