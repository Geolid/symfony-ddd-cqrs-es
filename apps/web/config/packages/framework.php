<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'session' => ['handler_id' => null, 'cookie_secure' => 'auto', 'cookie_samesite' => 'lax'],
        'form' => true,
        'csrf_protection' => true,
        'assets' => true,
        'asset_mapper' => true,
        'property_access' => true,
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }
};
