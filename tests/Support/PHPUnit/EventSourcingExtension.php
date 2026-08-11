<?php

declare(strict_types=1);

namespace Support\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Support\PHPUnit\EventSourcing\BootSubscriptionsOnTestSuiteStarted;
use Support\PHPUnit\EventSourcing\CreateSchemaOnTestSuiteStarted;
use Support\PHPUnit\EventSourcing\ResetDatabasesOnTestSuiteStarted;
use Support\PHPUnit\EventSourcing\ResetStateOnPreparationStarted;

/**
 * Registers event-sourcing test subscribers in dependency order — PHPUnit notifies same-event subscribers
 * in registration order.
 */
final class EventSourcingExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new ResetDatabasesOnTestSuiteStarted(),
            new CreateSchemaOnTestSuiteStarted(),
            new BootSubscriptionsOnTestSuiteStarted(),
            new ResetStateOnPreparationStarted(),
        );
    }
}
