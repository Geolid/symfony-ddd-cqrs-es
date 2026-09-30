<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()->autowire()->autoconfigure()
        ->load('Storefront\\', '../src/');

    $container->services()
        ->defaults()->autowire()->autoconfigure()
        ->load('Ui\\', '%kernel.project_dir%/ui/src/');

    if ('e2e' === $container->env()) {
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Storefront\\Tests\\Behat\\', '../tests/Behat/*Context.php');
    }
};
