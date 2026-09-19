<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Tools\PHPUnit\Valkey\FlushKeysOnPreparationStarted;

/**
 * Registers the subscriber that clears this worker's own Valkey keys before each test.
 */
final class ValkeyExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new FlushKeysOnPreparationStarted());
    }
}
