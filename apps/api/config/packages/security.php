<?php

declare(strict_types=1);

use Api\Security\ApiTokenAuthenticator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'firewalls' => [
            'api' => [
                'stateless' => true,
                'custom_authenticators' => [ApiTokenAuthenticator::class],
            ],
        ],
    ]);
};
