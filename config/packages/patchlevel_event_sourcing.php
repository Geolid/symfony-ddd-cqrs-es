<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('patchlevel_event_sourcing', [
        'aggregates' => ['%kernel.project_dir%/src/'],
        'events' => ['%kernel.project_dir%/src/'],
        'connection' => ['service' => 'doctrine.dbal.event_store_connection'],
        'store' => ['type' => 'dbal_stream'],
        'subscription' => [
            'gap_detection' => null,
            'run_after_aggregate_save' => [
                'enabled' => true,
                'groups' => ['translator', 'sync_processor'],
            ],
        ],
        'hydrator' => ['cryptography' => true],
    ]);

    if (in_array($container->env(), ['dev', 'demo'], true)) {
        $container->extension('patchlevel_event_sourcing', [
            'subscription' => [
                'catch_up' => true,
                'throw_on_error' => true,
                'run_after_aggregate_save' => true,
                'rebuild_after_file_change' => true,
                'auto_setup' => true,
            ],
        ]);
    }

    if ('test' === $container->env()) {
        $container->extension('patchlevel_event_sourcing', [
            'subscription' => [
                'store' => ['type' => 'static_in_memory'],
                'catch_up' => true,
                'throw_on_error' => true,
                'run_after_aggregate_save' => [
                    'enabled' => true,
                    'groups' => ['translator', 'projector', 'sync_processor'],
                ],
            ],
        ]);
    }
};
