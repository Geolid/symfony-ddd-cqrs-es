<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Service;

final readonly class ErasureRetentionCalculator
{
    public function __construct(private int $days)
    {
    }

    public function effectiveAt(\DateTimeImmutable $requestedAt): \DateTimeImmutable
    {
        return $requestedAt->modify(\sprintf('+%d days', $this->days));
    }

    public function cutoffAt(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(\sprintf('-%d days', $this->days));
    }
}
