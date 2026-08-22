<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
                'custom_authenticators' => [PasswordCredentialAuthenticator::class],
                'login_throttling' => [
                    'max_attempts' => 3,
                ],
                'remember_me' => [
                    'secret' => '%kernel.secret%',
                    'lifetime' => 604800,
                    'path' => '/',
                    'signature_properties' => ['authenticatable', 'passwordChangedAt'],
                ],
                'logout' => ['path' => '/logout', 'target' => 'sales_order_list'],
            ],
        ],
    ]);
};
