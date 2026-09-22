<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\DeviceTrust;

final readonly class DeviceTrustResult
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $revokedAt,
    ) {
    }
}
