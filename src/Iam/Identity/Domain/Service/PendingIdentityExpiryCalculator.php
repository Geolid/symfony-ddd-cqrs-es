<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

final readonly class PendingIdentityExpiryCalculator
{
    public function __construct(private \DateInterval $expiry)
    {
    }

    public function expiresAt(\DateTimeImmutable $registeredAt): \DateTimeImmutable
    {
        return $registeredAt->add($this->expiry);
    }
}
