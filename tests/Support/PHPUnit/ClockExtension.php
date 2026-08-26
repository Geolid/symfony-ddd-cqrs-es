<?php

declare(strict_types=1);

namespace Support\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Support\PHPUnit\Clock\FreezeClockOnTestSuiteStarted;

/**
 * Registers the subscriber that freezes the global Clock before the test suite starts.
 */
final class ClockExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new FreezeClockOnTestSuiteStarted());
    }
}
