<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Service;

final readonly class CheckoutSessionExpiryCalculator
{
    private const int TTL_MINUTES = 30;

    public function expiresAt(\DateTimeImmutable $openedAt): \DateTimeImmutable
    {
        return $openedAt->modify(\sprintf('+%d minutes', self::TTL_MINUTES));
    }
}
