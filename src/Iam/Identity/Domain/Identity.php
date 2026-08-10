<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\IdentityState;
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
    private IdentityId $id;
    private IdentityState $status;
    private bool $erased;

    public function id(): IdentityId
    {
        return $this->id;
    }

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
    public function suspend(\DateTimeImmutable $suspendedAt): void
    {
        if ($this->erased) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->status->isSuspended()) {
            return;
        }

        $this->recordThat(new IdentitySuspended(
            id: $this->id->toString(),
            suspendedAt: $suspendedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws IdentityAlreadyErasedException
     */
    public function reactivate(\DateTimeImmutable $reactivatedAt): void
    {
        if ($this->erased) {
            throw IdentityAlreadyErasedException::forId($this->id);
        }

        if ($this->status->isActive()) {
            return;
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id->toString(),
            reactivatedAt: $reactivatedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if ($this->erased) {
            return;
        }

        $this->recordThat(new IdentityErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyIdentityRegistered(IdentityRegistered $event): void
    {
        $this->id = IdentityId::fromString($event->id);
        $this->status = IdentityState::ACTIVE;
        $this->erased = false;
    }

    #[Apply]
    private function applyIdentityErased(IdentityErased $event): void
    {
        $this->erased = true;
    }

    #[Apply]
    private function applyIdentitySuspended(IdentitySuspended $event): void
    {
        $this->status = IdentityState::SUSPENDED;
    }

    #[Apply]
    private function applyIdentityReactivated(IdentityReactivated $event): void
    {
        $this->status = IdentityState::ACTIVE;
    }
}
