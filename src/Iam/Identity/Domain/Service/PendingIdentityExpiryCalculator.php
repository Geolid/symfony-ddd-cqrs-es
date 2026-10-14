<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

final readonly class PendingIdentityExpiryCalculator
{
    private const int HOURS = 24;

    public function expiresAt(\DateTimeImmutable $registeredAt): \DateTimeImmutable
    {
        return $registeredAt->modify(\sprintf('+%d hours', self::HOURS));
    }
}
