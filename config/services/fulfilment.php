<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Fulfilment');

    if ('test' === $container->env()) {
        $services->get(CreateShipmentOnOrderPlaced::class)->public();
    }
};
