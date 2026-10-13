<?php

declare(strict_types=1);

namespace Support\TestCase;

use PHPUnit\Framework\Assert;

trait AssertionTrait
{
    protected static function assertSameDateTime(?\DateTimeInterface $expected, ?\DateTimeInterface $actual): void
    {
        Assert::assertSame(
            $expected?->format(\DateTimeInterface::ATOM),
            $actual?->format(\DateTimeInterface::ATOM),
        );
    }
}
