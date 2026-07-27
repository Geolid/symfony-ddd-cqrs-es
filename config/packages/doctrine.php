<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'default_connection' => 'read_model',
            'connections' => [
                // Write side: the append-only event store (see patchlevel_event_sourcing.php).
                'event_store' => ['url' => '%env(resolve:EVENT_STORE_URL)%'],
                // Read side: the projection tables built by Projectors and served by Finders.
                'read_model' => ['url' => '%env(resolve:READ_MODEL_URL)%'],
                // Messenger's own doctrine transport (queued/failed async messages).
                'messenger' => ['url' => '%env(resolve:MESSENGER_URL)%'],
            ],
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('doctrine', [
            'dbal' => [
                'connections' => [
                    'event_store' => ['dbname_suffix' => '_test%env(default::TEST_TOKEN)%'],
                    'read_model' => ['dbname_suffix' => '_test%env(default::TEST_TOKEN)%'],
                ],
            ],
        ]);
    }
};
