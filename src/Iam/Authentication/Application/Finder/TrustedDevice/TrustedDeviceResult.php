<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TrustedDevice;

final readonly class TrustedDeviceResult
{
    public function __construct(
        public string $id,
        public string $identityId,
        public int $version,
        public string $userAgent,
        public string $ip,
        public \DateTimeImmutable $trustedAt,
    ) {
    }
}
