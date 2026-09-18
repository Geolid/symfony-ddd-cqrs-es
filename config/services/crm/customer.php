<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Infrastructure\Projection\Finder\DbalCustomerFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Crm\\Customer');

    if ('test' === $container->env()) {
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
    }
};
