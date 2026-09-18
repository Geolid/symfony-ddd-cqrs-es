<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Infrastructure\Projection\Finder\DbalIdentityFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Identity');

    if ('test' === $container->env()) {
        $services->alias(IdentityFinderInterface::class, DbalIdentityFinder::class)->public();
    }
};
