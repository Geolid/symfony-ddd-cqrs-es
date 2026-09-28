<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust\Event;

use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.device_trust.revoked')]
final readonly class DeviceTrustRevoked
{
    public function __construct(
        public DeviceTrustId $id,
        public string $identityId,
        public \DateTimeImmutable $revokedAt,
    ) {
    }
}
