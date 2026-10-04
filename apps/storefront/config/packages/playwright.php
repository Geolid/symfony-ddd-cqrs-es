<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if ('test' === $container->env() && '1' === getenv('BROWSER_HEADED')) {
        $container->extension('playwright', [
            'browsers' => [
                'default' => [
                    'headless' => false,
                ],
            ],
        ]);
    }
};
