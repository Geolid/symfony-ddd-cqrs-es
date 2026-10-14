<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class CheckoutSessionExpiryCalculator
{
    public function __construct(private int $minutes)
    {
        Assert::positiveInteger($this->minutes);
    }

    public function expiresAt(\DateTimeImmutable $openedAt): \DateTimeImmutable
    {
        return $openedAt->modify(\sprintf('+%d minutes', $this->minutes));
    }
}
