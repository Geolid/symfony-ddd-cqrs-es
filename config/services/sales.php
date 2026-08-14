<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Infrastructure\Persistence\Projection\Finder\DbalCustomerFinder;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Infrastructure\Payment\OrderPaymentRequestingService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('sales.billing_retention_days', 3650);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Sales');

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
        $services->alias(OrderPaymentRequesterInterface::class, OrderPaymentRequestingService::class)->public();
    }
};
