<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Clock;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

/**
 * Freezes the global Clock to a fixed instant once before the test suite starts, so any
 * Clock::get()->now() read stays deterministic, and synchronizes ClockMock to intercept
 * native PHP time functions.
 */
final class FreezeClockOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasBeenFrozen = false;

    public function notify(Started $event): void
    {
        if (self::$hasBeenFrozen) {
            return;
        }

        Clock::set(new MockClock('2026-01-01T00:00:00+00:00'));
        ClockMock::withClockMock(Clock::get()->now()->getTimestamp());

        self::$hasBeenFrozen = true;
    }
}
