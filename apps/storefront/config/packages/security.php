<?php

declare(strict_types=1);

use Storefront\Security\Authenticator\PasswordCredentialAuthenticator;
use Storefront\Security\Provider\PasswordUserProvider;
use Storefront\Security\UserChecker\PasswordUserChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
                'user_checker' => PasswordUserChecker::class,
                'custom_authenticators' => [PasswordCredentialAuthenticator::class],
                'login_throttling' => [
                    'max_attempts' => 3,
                    'interval' => '15 minutes',
                ],
                'remember_me' => [
                    'secret' => '%kernel.secret%',
                    'lifetime' => 604800,
                    'path' => '/',
                    'always_remember_me' => true,
                    'signature_properties' => ['passwordChangedAt'],
                ],
                'two_factor' => [
                    'auth_form_path' => 'storefront_two_factor_challenge',
                    'check_path' => 'storefront_two_factor_challenge_check',
                    'default_target_path' => 'storefront_home_show',
                    'enable_csrf' => true,
                ],
                'logout' => ['path' => '/logout', 'target' => 'storefront_signin_identify'],
            ],
        ],
    ]);
};
