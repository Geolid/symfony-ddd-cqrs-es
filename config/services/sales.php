<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Order\Application\Payment\RequestOrderPaymentInterface;
use Sales\Order\Infrastructure\Payment\OrderPaymentRequestingService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Sales');

    if ('test' === $container->env()) {
        // This #[AsDrivingPort] is only ever consumed by a DM (apps/web) — a bare BC-level test
        // container has no such consumer, so the compiler would otherwise prune it as unused.
        $services->alias(RequestOrderPaymentInterface::class, OrderPaymentRequestingService::class)->public();
    }
};
