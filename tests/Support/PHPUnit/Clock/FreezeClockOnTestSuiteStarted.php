<?php

declare(strict_types=1);

namespace Support\PHPUnit\Clock;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

/**
 * Freezes the global Clock to a fixed instant once before the test suite starts, so a Test
 * Factory's own Clock::get()->now() default stays deterministic without needing the Kernel/container.
 */
final class FreezeClockOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasBeenFrozen = false;

    public function notify(Started $event): void
    {
        if (self::$hasBeenFrozen) {
            return;
        }

        Clock::set(new MockClock('2030-01-01T00:00:00+00:00'));

        self::$hasBeenFrozen = true;
    }
}
