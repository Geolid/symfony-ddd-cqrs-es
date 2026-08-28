<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Tools\PHPUnit\Faker\PrintSeedOnTestSuiteStarted;

/**
 * Registers the subscriber that resolves and prints the Faker seed before the test suite starts.
 */
final class FakerSeedExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new PrintSeedOnTestSuiteStarted());
    }
}
