<?php

declare(strict_types=1);

namespace Support;

use Symfony\Component\Clock\Clock;

/**
 * Ticks Clock::get()->now() forward by one second on every call — monotonically increasing,
 * reset before each test.
 */
final class ClockSequence
{
    private static int $tick = 0;

    public static function next(): \DateTimeImmutable
    {
        ++self::$tick;

        return Clock::get()->now()->modify(\sprintf('+%d seconds', self::$tick));
    }

    public static function reset(): void
    {
        self::$tick = 0;
    }
}
