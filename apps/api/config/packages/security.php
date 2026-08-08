<?php

declare(strict_types=1);

use Api\Security\ApiTokenCredentialAuthenticator;
use Api\Security\ApiUserProvider;
use Api\Security\IdentityChecker;
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
                'user_checker' => IdentityChecker::class,
                'custom_authenticators' => [ApiTokenCredentialAuthenticator::class],
            ],
        ],
    ]);
};
