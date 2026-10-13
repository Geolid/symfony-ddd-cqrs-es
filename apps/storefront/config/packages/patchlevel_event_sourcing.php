<?php

declare(strict_types=1);

use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Policy;
use Shared\Infrastructure\Processor;
use Shared\Infrastructure\Projection\Projector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if ('test' === $container->env()) {
        $container->extension('patchlevel_event_sourcing', [
            'subscription' => [
                'run_after_aggregate_save' => [
                    'enabled' => true,
                    'groups' => [Publisher::GROUP, Projector::GROUP, Policy::GROUP, Processor::GROUP],
                ],
            ],
        ]);
    }
};
