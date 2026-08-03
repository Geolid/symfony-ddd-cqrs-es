<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'http_client' => [
            'scoped_clients' => [
                'acme.client' => [
                    'base_uri' => '%env(ACME_BASE_URL)%',
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'auth_bearer' => '%env(ACME_API_TOKEN)%',
                ],
                'globex.client' => [
                    'base_uri' => '%env(GLOBEX_BASE_URL)%',
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'auth_bearer' => '%env(GLOBEX_API_TOKEN)%',
                ],
            ],
        ],
    ]);
};
