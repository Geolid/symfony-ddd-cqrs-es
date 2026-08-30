<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Tools\PHPUnit\Clock\FreezeClockOnTestSuiteStarted;
use Tools\PHPUnit\Clock\ResetSequenceOnPreparationStarted;

/**
 * Registers the subscribers that freeze the global Clock before the test suite starts and reset
 * the Test Factory clock sequence before each test.
 */
final class ClockExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new FreezeClockOnTestSuiteStarted(),
            new ResetSequenceOnPreparationStarted(),
        );
    }
}
