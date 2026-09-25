<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Event;

use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.trusted_device.trusted')]
final readonly class TrustedDeviceTrusted
{
    public function __construct(
        public TrustedDeviceId $id,
        public string $identityId,
        public int $version,
        public string $userAgent,
        public string $ip,
        public \DateTimeImmutable $trustedAt,
    ) {
    }
}
