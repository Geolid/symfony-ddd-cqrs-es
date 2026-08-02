<?php

declare(strict_types=1);

use Iam\Identity\Infrastructure\Security\IdentityStatusUserChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Web\Security\IamUserProvider;
use Web\Security\PasswordCredentialAuthenticator;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'providers' => [
            'iam_user_provider' => ['id' => IamUserProvider::class],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|assets)/',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'iam_user_provider',
                'user_checker' => IdentityStatusUserChecker::class,
                'custom_authenticators' => [PasswordCredentialAuthenticator::class],
                'logout' => ['path' => 'security_logout', 'target' => 'sales_order_list'],
            ],
        ],
    ]);
};
