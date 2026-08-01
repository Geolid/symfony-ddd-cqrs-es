<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'trusted_proxies' => 'REMOTE_ADDR',
        'trusted_headers' => ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-forwarded-prefix', 'x-forwarded-port'],
        'session' => false,
        'form' => false,
        'csrf_protection' => false,
        'assets' => false,
        'asset_mapper' => false,
        'property_access' => false,
        'web_link' => false,
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'test' => true,
        ]);
    }
};
