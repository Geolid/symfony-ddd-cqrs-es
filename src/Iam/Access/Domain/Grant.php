<?php

declare(strict_types=1);

namespace Iam\Access\Domain;

use Iam\Access\Domain\Event\PermissionGranted;
use Iam\Access\Domain\Event\PermissionReactivated;
use Iam\Access\Domain\Event\PermissionRevoked;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Access\Domain\ValueObject\Permission;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.access.grant')]
final class Grant implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private GrantId $id;
    private bool $revoked;

    public function id(): GrantId
    {
        return $this->id;
    }

    public static function grant(GrantId $id, string $identityId, Permission $permission, \DateTimeImmutable $grantedAt): self
    {
        $self = new self();
        $self->recordThat(new PermissionGranted(
            id: $id->toString(),
            identityId: $identityId,
            permission: $permission->toString(),
            grantedAt: $grantedAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        if ($this->revoked) {
            return;
        }

        $this->recordThat(new PermissionRevoked(
            id: $this->id->toString(),
            revokedAt: $revokedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function reactivate(\DateTimeImmutable $reactivatedAt): void
    {
        if (!$this->revoked) {
            return;
        }

        $this->recordThat(new PermissionReactivated(
            id: $this->id->toString(),
            reactivatedAt: $reactivatedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyPermissionGranted(PermissionGranted $event): void
    {
        $this->id = GrantId::fromString($event->id);
        $this->revoked = false;
    }

    #[Apply]
    private function applyPermissionRevoked(PermissionRevoked $event): void
    {
        $this->revoked = true;
    }

    #[Apply]
    private function applyPermissionReactivated(PermissionReactivated $event): void
    {
        $this->revoked = false;
    }
}
