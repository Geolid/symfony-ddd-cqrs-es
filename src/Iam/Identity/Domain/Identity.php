<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.identity')]
final class Identity implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) IdentityId $id;
    private IdentityState $state;

    public static function register(IdentityId $id, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new IdentityRegistered(
            id: $id->toString(),
            registeredAt: $registeredAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    /**
     * @throws IdentityAlreadyErasedException
     */
    public function suspend(Reason $reason, \DateTimeImmutable $suspendedAt): void
    {
        if ($this->state->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->state->isSuspended()) {
            return;
        }

        $this->recordThat(new IdentitySuspended(
            id: $this->id->toString(),
            reason: $reason->value,
            suspendedAt: $suspendedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     */
    public function reactivate(\DateTimeImmutable $reactivatedAt): void
    {
        if ($this->state->isErased()) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->state->isActive()) {
            return;
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id->toString(),
            reactivatedAt: $reactivatedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws IdentityNotActiveException
     */
    public function ensureActive(): void
    {
        if (!$this->state->isActive()) {
            throw IdentityNotActiveException::forId($this->id);
        }
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if ($this->state->isErased()) {
            return;
        }

        $this->recordThat(new IdentityErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyRegistered(IdentityRegistered $event): void
    {
        $this->id = IdentityId::fromString($event->id);
        $this->state = IdentityState::ACTIVE;
    }

    #[Apply]
    private function applyErased(IdentityErased $event): void
    {
        $this->state = IdentityState::ERASED;
    }

    #[Apply]
    private function applySuspended(IdentitySuspended $event): void
    {
        $this->state = IdentityState::SUSPENDED;
    }

    #[Apply]
    private function applyReactivated(IdentityReactivated $event): void
    {
        $this->state = IdentityState::ACTIVE;
    }
}
