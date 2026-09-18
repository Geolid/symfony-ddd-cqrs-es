<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface as CheckoutCartFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface as CheckoutCartItemFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Tax\TaxRateResolverInterface;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCartFinder as CheckoutDbalCartFinder;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCartItemFinder as CheckoutDbalCartItemFinder;
use Shopping\Checkout\Infrastructure\Projection\Finder\DbalCustomerFinder;
use Shopping\Checkout\Infrastructure\Tax\StaticTaxRateResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Shopping\\Checkout');

    if ('test' === $container->env()) {
        $services->alias(CheckoutCartFinderInterface::class, CheckoutDbalCartFinder::class)->public();
        $services->alias(CheckoutCartItemFinderInterface::class, CheckoutDbalCartItemFinder::class)->public();
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
        $services->alias(TaxRateResolverInterface::class, StaticTaxRateResolver::class)->public();
    }
};
