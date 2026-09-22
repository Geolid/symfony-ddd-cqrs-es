<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust;

use Iam\Authentication\Domain\DeviceTrust\Event\DeviceTrustRevoked;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.device_trust')]
final class DeviceTrust implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) DeviceTrustId $id;

    public static function establish(DeviceTrustId $id, string $identityId, \DateTimeImmutable $revokedAt): self
    {
        $self = new self();
        $self->recordThat(new DeviceTrustRevoked($id, $identityId, $revokedAt));

        return $self;
    }

    public function revoke(string $identityId, \DateTimeImmutable $revokedAt): void
    {
        $this->recordThat(new DeviceTrustRevoked($this->id, $identityId, $revokedAt));
    }

    #[Apply]
    private function applyRevoked(DeviceTrustRevoked $event): void
    {
        $this->id = $event->id;
    }
}
