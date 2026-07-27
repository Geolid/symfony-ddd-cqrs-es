<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Shipping\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shipping');

    if ('test' === $container->env()) {
        // Lets tests exercise the Processor directly instead of running a subscription worker.
        $services->get(CreateShipmentOnOrderPlaced::class)->public();
    }
};
