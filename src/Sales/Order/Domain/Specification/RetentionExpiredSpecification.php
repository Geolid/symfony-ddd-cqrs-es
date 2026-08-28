<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Specification;

final readonly class RetentionExpiredSpecification
{
    public const int DAYS = 3650;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $closedAt): bool
    {
        return $this->now > $closedAt->modify(\sprintf('+%d days', self::DAYS));
    }
}
