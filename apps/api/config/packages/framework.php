<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'assets' => true,
        'csrf_protection' => true,
        'property_access' => true,
        'session' => ['handler_id' => null, 'cookie_secure' => 'auto', 'cookie_samesite' => 'lax'],
    ]);
};
