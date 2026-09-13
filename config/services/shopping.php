<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface as CartCartFinderInterface;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface as CartCartItemFinderInterface;
use Shopping\Cart\Application\Finder\ListedProduct\ListedProductFinderInterface as CartListedProductFinderInterface;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalCartFinder as CartDbalCartFinder;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalCartItemFinder as CartDbalCartItemFinder;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalListedProductFinder as CartDbalListedProductFinder;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface as CheckoutCartFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface as CheckoutCartItemFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCartFinder as CheckoutDbalCartFinder;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCartItemFinder as CheckoutDbalCartItemFinder;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCustomerFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shopping');

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CartCartFinderInterface::class, CartDbalCartFinder::class)->public();
        $services->alias(CartCartItemFinderInterface::class, CartDbalCartItemFinder::class)->public();
        $services->alias(CartListedProductFinderInterface::class, CartDbalListedProductFinder::class)->public();
        $services->alias(CheckoutCartFinderInterface::class, CheckoutDbalCartFinder::class)->public();
        $services->alias(CheckoutCartItemFinderInterface::class, CheckoutDbalCartItemFinder::class)->public();
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
    }
};
