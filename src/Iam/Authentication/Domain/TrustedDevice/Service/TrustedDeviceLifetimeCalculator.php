<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Service;

final readonly class TrustedDeviceLifetimeCalculator
{
    public function __construct(private int $seconds)
    {
    }

    public function expiresAt(\DateTimeImmutable $trustedAt): \DateTimeImmutable
    {
        return $trustedAt->modify(\sprintf('+%d seconds', $this->seconds));
    }
}
