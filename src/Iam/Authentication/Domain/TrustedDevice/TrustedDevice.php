<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice;

use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceRevoked;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceTrusted;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.trusted_device')]
final class TrustedDevice implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) TrustedDeviceId $id;
    private string $identityId;
    private bool $revoked;

    public static function trust(
        TrustedDeviceId $id,
        string $identityId,
        string $userAgent,
        string $ip,
        \DateTimeImmutable $trustedAt,
    ): self {
        $self = new self();
        $self->recordThat(new TrustedDeviceTrusted(
            id: $id,
            identityId: $identityId,
            userAgent: $userAgent,
            ip: $ip,
            trustedAt: $trustedAt,
        ));

        return $self;
    }

    /**
     * @throws TrustedDeviceOwnedByAnotherIdentityException
     */
    public function revoke(string $identityId, \DateTimeImmutable $revokedAt): void
    {
        if ($this->identityId !== $identityId) {
            throw TrustedDeviceOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->revoked) {
            return;
        }

        $this->recordThat(new TrustedDeviceRevoked(
            id: $this->id,
            revokedAt: $revokedAt,
        ));
    }

    #[Apply]
    private function applyTrusted(TrustedDeviceTrusted $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->revoked = false;
    }

    #[Apply]
    private function applyRevoked(TrustedDeviceRevoked $event): void
    {
        $this->revoked = true;
    }
}
