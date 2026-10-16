<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Service;

final readonly class CheckoutSessionExpiryCalculator
{
    public function __construct(private \DateInterval $expiry)
    {
    }

    public function expiresAt(\DateTimeImmutable $openedAt): \DateTimeImmutable
    {
        return $openedAt->add($this->expiry);
    }
}
