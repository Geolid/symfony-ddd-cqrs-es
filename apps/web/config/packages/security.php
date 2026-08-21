<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Web\Security\IdentityChecker;
use Web\Security\PasswordCredentialAuthenticator;
use Web\Security\PasswordUserProvider;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'providers' => [
            'password_users' => ['id' => PasswordUserProvider::class],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|assets)/',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'password_users',
                'user_checker' => IdentityChecker::class,
                'custom_authenticators' => [PasswordCredentialAuthenticator::class],
                'login_throttling' => [
                    'max_attempts' => 3,
                ],
                'logout' => ['path' => '/logout', 'target' => 'sales_order_list'],
            ],
        ],
    ]);
};
