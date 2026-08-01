<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'default_connection' => 'read_model',
            'connections' => [
                'event_store' => ['url' => '%env(resolve:EVENT_STORE_URL)%'],
                'read_model' => ['url' => '%env(resolve:READ_MODEL_URL)%'],
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
