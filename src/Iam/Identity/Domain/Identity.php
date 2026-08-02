<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
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
    private IdentityStatus $status;

    public function id(): IdentityId
    {
        return $this->id;
    }

    public function status(): IdentityStatus
    {
        return $this->status;
    }

    public static function register(IdentityId $id, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new IdentityRegistered(
            id: $id->toString(),
            registeredAt: $registeredAt->format('c'),
        ));

        return $self;
    }

    /**
     * @throws IdentityAlreadySuspendedException
     */
    public function suspend(\DateTimeImmutable $suspendedAt): void
    {
        if (IdentityStatus::SUSPENDED === $this->status) {
            throw IdentityAlreadySuspendedException::forId($this->id);
        }

        $this->recordThat(new IdentitySuspended(
            id: $this->id->toString(),
            suspendedAt: $suspendedAt->format('c'),
        ));
    }

    /**
     * @throws IdentityNotSuspendedException
     */
    public function reactivate(\DateTimeImmutable $reactivatedAt): void
    {
        if (IdentityStatus::ACTIVE === $this->status) {
            throw IdentityNotSuspendedException::forId($this->id);
        }

        $this->recordThat(new IdentityReactivated(
            id: $this->id->toString(),
            reactivatedAt: $reactivatedAt->format('c'),
        ));
    }

    #[Apply]
    private function applyIdentityRegistered(IdentityRegistered $event): void
    {
        $this->id = IdentityId::fromString($event->id);
        $this->status = IdentityStatus::ACTIVE;
    }

    #[Apply]
    private function applyIdentitySuspended(IdentitySuspended $event): void
    {
        $this->status = IdentityStatus::SUSPENDED;
    }

    #[Apply]
    private function applyIdentityReactivated(IdentityReactivated $event): void
    {
        $this->status = IdentityStatus::ACTIVE;
    }
}
