<?php

declare(strict_types=1);

namespace Support\TestCase;

use PHPUnit\Framework\Assert;

trait AssertionTrait
{
    /**
     * Compared to the second: the database keeps no microseconds.
     */
    protected static function assertSameDate(?\DateTimeInterface $expected, ?\DateTimeInterface $actual): void
    {
        Assert::assertSame(
            $expected?->format(\DateTimeInterface::ATOM),
            $actual?->format(\DateTimeInterface::ATOM),
        );
    }
}
