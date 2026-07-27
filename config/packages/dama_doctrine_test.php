<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if ('test' === $container->env()) {
        $container->extension('dama_doctrine_test', [
            'enable_static_connection' => true,
        ]);
    }
};
