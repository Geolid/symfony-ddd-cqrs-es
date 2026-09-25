<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Event;

use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.trusted_device.revoked')]
final readonly class TrustedDeviceRevoked
{
    public function __construct(
        public TrustedDeviceId $id,
        public \DateTimeImmutable $revokedAt,
    ) {
    }
}
