<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Shopping\Checkout\Domain\Service\CheckoutSessionExpiryCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Shopping\\Checkout');

    $container->parameters()->set('shopping.checkout.session_expiry_minutes', 30);
    $services->get(CheckoutSessionExpiryCalculator::class)->arg('$minutes', '%shopping.checkout.session_expiry_minutes%');
};
