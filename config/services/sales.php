<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Infrastructure\Projection\Finder\DbalBuyerFinder;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Ordering\Infrastructure\Projection\Finder\DbalCartFinder;
use Sales\Ordering\Infrastructure\Projection\Finder\DbalCartLineFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Sales');

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(BuyerFinderInterface::class, DbalBuyerFinder::class)->public();
        $services->alias(CartFinderInterface::class, DbalCartFinder::class)->public();
        $services->alias(CartLineFinderInterface::class, DbalCartLineFinder::class)->public();
    }
};
