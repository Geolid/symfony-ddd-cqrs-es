<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class ErasureRetentionCalculator
{
    public function __construct(private int $days)
    {
        Assert::positiveInteger($this->days);
    }

    public function effectiveAt(\DateTimeImmutable $requestedAt): \DateTimeImmutable
    {
        return $requestedAt->modify(\sprintf('+%d days', $this->days));
    }
}
