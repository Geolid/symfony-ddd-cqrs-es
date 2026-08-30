<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Tools\PHPUnit\AggregateFactory\ResetSequenceOnPreparationStarted;

/**
 * Registers the subscriber that resets the Test Factory creation-instant sequence before each test.
 */
final class AggregateFactoryExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new ResetSequenceOnPreparationStarted());
    }
}
