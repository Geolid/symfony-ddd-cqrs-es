<?php

declare(strict_types=1);

use Api\Security\ApiTokenAuthenticator;
use Iam\Identity\Infrastructure\Security\IdentityStatusUserChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'firewalls' => [
            'api' => [
                'stateless' => true,
                'user_checker' => IdentityStatusUserChecker::class,
                'custom_authenticators' => [ApiTokenAuthenticator::class],
            ],
        ],
    ]);
};
