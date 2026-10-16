<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Service;

final readonly class TrustedDeviceExpiryCalculator
{
    public function __construct(private \DateInterval $expiry)
    {
    }

    public function expiresAt(\DateTimeImmutable $trustedAt): \DateTimeImmutable
    {
        return $trustedAt->add($this->expiry);
    }
}
