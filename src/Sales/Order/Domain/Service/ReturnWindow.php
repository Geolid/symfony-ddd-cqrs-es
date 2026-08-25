<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Service;

final readonly class ReturnWindow
{
    public function __construct(private int $days)
    {
    }

    public function hasExpired(\DateTimeImmutable $deliveredAt, \DateTimeImmutable $now): bool
    {
        return $now > $deliveredAt->modify(\sprintf('+%d days', $this->days));
    }

    public function cutoffFor(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(\sprintf('-%d days', $this->days));
    }
}
