<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface as CartCartFinderInterface;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface as CartCartItemFinderInterface;
use Shopping\Cart\Application\Finder\ListedProduct\ListedProductFinderInterface as CartListedProductFinderInterface;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalCartFinder as CartDbalCartFinder;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalCartItemFinder as CartDbalCartItemFinder;
use Shopping\Cart\Infrastructure\Projection\Finder\DbalListedProductFinder as CartDbalListedProductFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Shopping', 'Cart');

    if ('test' === $container->env()) {
        $services->alias(CartCartFinderInterface::class, CartDbalCartFinder::class)->public();
        $services->alias(CartCartItemFinderInterface::class, CartDbalCartItemFinder::class)->public();
        $services->alias(CartListedProductFinderInterface::class, CartDbalListedProductFinder::class)->public();
    }
};
