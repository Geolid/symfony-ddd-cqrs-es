<?php

declare(strict_types=1);

use Storefront\Security\Provider\TrustedDeviceManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('scheb_two_factor', [
        'trusted_device' => [
            'enabled' => true,
            'lifetime' => 2592000, // 30 days
            'manager' => TrustedDeviceManager::class,
        ],
    ]);
};
