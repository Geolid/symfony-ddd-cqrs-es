<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    // A scoped client's base URI is resolved when the container compiles, not when the client
    // is first used — an undefined value breaks the boot rather than the call.
    $container->parameters()
        ->set('env(ACME_BASE_URL)', 'https://carrier.acme.test')
        ->set('env(ACME_API_TOKEN)', 'change-me');

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
            ],
        ],
    ]);
};
