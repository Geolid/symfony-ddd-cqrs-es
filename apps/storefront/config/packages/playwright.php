<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if ('test' === $container->env()) {
        $container->extension('playwright', [
            'browsers' => [
                'default' => [
                    'headless' => '%env(not:default::BROWSER_HEADED)%',
                    'slowmo_ms' => '1' === getenv('BROWSER_HEADED') ? 300 : 0,
                ],
            ],
        ]);
    }
};
