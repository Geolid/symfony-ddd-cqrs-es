<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if ('dev' === $container->env()) {
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Demo\\', '%kernel.project_dir%/demo/**/*{Command}.php');
    }
};
