<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Service;

use Webmozart\Assert\Assert;

final readonly class TrustedDeviceExpiryCalculator
{
    public function __construct(private int $seconds)
    {
        Assert::positiveInteger($this->seconds);
    }

    public function expiresAt(\DateTimeImmutable $trustedAt): \DateTimeImmutable
    {
        return $trustedAt->modify(\sprintf('+%d seconds', $this->seconds));
    }
}
