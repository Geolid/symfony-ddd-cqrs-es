<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'router' => [
            'default_uri' => '%env(app_url:DEFAULT_URI)%',
        ],
    ]);

    if ('prod' === $container->env()) {
        $container->extension('framework', [
            'router' => [
                'strict_requirements' => null,
            ],
        ]);
    }
};
