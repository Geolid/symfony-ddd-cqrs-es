<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Shopping\Checkout\Domain\Service\CheckoutSessionExpiryCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Shopping\\Checkout');

    $container->parameters()->set('shopping.checkout.session_expiry', '30 minutes');
    $services->get(CheckoutSessionExpiryCalculator::class)->arg('$expiry', inline_service(DateInterval::class)->factory([DateInterval::class, 'createFromDateString'])->args(['%shopping.checkout.session_expiry%']));
};
