<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Tools\PHPUnit\Clock\ResetSequenceOnPreparationStarted;

/**
 * Freezes the global Clock before the test suite is built, so a data provider and a test read the
 * same instant, and registers the subscriber that resets the Test Factory clock sequence before each test.
 */
final class ClockExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        Clock::set(new MockClock('2026-01-01T00:00:00+00:00'));
        ClockMock::withClockMock(Clock::get()->now()->getTimestamp());

        $facade->registerSubscriber(new ResetSequenceOnPreparationStarted());
    }
}
