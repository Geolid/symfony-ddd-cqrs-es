<?php

declare(strict_types=1);

namespace Support\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Support\PHPUnit\AggregateFactory\ResetSequenceOnPreparationStarted;

/**
 * Registers the subscriber that resets aggregate sequences before each test.
 */
final class AggregateFactoryExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new ResetSequenceOnPreparationStarted());
    }
}
