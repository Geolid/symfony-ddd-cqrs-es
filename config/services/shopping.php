<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCartItemFinder;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCustomerFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shopping');

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
        $services->alias(CartItemFinderInterface::class, DbalCartItemFinder::class)->public();
    }
};
