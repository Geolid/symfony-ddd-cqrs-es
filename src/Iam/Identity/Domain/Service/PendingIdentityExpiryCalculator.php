<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class PendingIdentityExpiryCalculator
{
    public function __construct(private int $hours)
    {
        Assert::positiveInteger($this->hours);
    }

    public function expiresAt(\DateTimeImmutable $registeredAt): \DateTimeImmutable
    {
        return $registeredAt->modify(\sprintf('+%d hours', $this->hours));
    }
}
