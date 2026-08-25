<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Service;

final readonly class RetentionWindow
{
    public function __construct(private int $days)
    {
    }

    public function hasExpired(\DateTimeImmutable $closedAt, \DateTimeImmutable $now): bool
    {
        return $now > $closedAt->modify(\sprintf('+%d days', $this->days));
    }

    public function cutoffFor(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(\sprintf('-%d days', $this->days));
    }
}
