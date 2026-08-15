<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => 'auto',
        ],
        'providers' => [
            'es_admin_users' => [
                'memory' => [
                    'users' => [
                        'admin' => [
                            'password' => '%env(base64:ES_ADMIN_PASSWORD_HASH)%',
                            'roles' => ['ROLE_ES_ADMIN'],
                        ],
                    ],
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|assets)/',
                'security' => false,
            ],
            'es_admin' => [
                'pattern' => '^/',
                'stateless' => true,
                'provider' => 'es_admin_users',
                'http_basic' => ['realm' => 'ES Admin'],
            ],
        ],
        'access_control' => [
            ['path' => '^/', 'roles' => 'ROLE_ES_ADMIN'],
        ],
    ]);
};
