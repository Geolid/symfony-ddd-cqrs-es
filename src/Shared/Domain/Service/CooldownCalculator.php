<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Webmozart\Assert\Assert;

final readonly class CooldownCalculator
{
    public function __construct(private int $seconds)
    {
        Assert::positiveInteger($this->seconds);
    }

    public function retryAt(\DateTimeImmutable $lastRequestedAt): \DateTimeImmutable
    {
        return $lastRequestedAt->modify(\sprintf('+%d seconds', $this->seconds));
    }
}
