<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Specification;

final readonly class ReturnWindowExpiredSpecification
{
    public const int DAYS = 14;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $deliveredAt): bool
    {
        return $this->now > $deliveredAt->modify(\sprintf('+%d days', self::DAYS));
    }
}
