<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class CheckoutSessionExpiryCalculator
{
    public function __construct(private int $seconds)
    {
        Assert::positiveInteger($this->seconds);
    }

    public function expiresAt(\DateTimeImmutable $openedAt): \DateTimeImmutable
    {
        return $openedAt->modify(\sprintf('+%d seconds', $this->seconds));
    }
}
