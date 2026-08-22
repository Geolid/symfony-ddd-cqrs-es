<?php

declare(strict_types=1);

use Api\Security\ApiKeyCredentialAuthenticator;
use Api\Security\ApiUserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'providers' => [
            'api_users' => ['id' => ApiUserProvider::class],
        ],
        'firewalls' => [
            'api' => [
                'stateless' => true,
                'provider' => 'api_users',
                'custom_authenticators' => [ApiKeyCredentialAuthenticator::class],
            ],
        ],
    ]);
};
