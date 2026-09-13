<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Specification;

final readonly class CheckoutSessionExpiredSpecification
{
    public const int TTL_MINUTES = 30;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $openedAt): bool
    {
        return $this->now > $openedAt->modify(\sprintf('+%d minutes', self::TTL_MINUTES));
    }
}
