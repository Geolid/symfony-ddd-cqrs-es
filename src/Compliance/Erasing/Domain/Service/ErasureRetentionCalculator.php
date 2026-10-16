<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class ErasureRetentionCalculator
{
    public function __construct(private int $seconds)
    {
        Assert::positiveInteger($this->seconds);
    }

    public function effectiveAt(\DateTimeImmutable $requestedAt): \DateTimeImmutable
    {
        return $requestedAt->modify(\sprintf('+%d seconds', $this->seconds));
    }
}
