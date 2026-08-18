<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Fulfilment\Shipment\Application\Processor\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\Processor\RequestShipmentOnOrderConfirmed;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fulfilment.return_address_first_name', '%env(FULFILMENT_RETURN_ADDRESS_FIRST_NAME)%');
    $container->parameters()->set('fulfilment.return_address_last_name', '%env(FULFILMENT_RETURN_ADDRESS_LAST_NAME)%');
    $container->parameters()->set('fulfilment.return_address_street', '%env(FULFILMENT_RETURN_ADDRESS_STREET)%');
    $container->parameters()->set('fulfilment.return_address_postal_code', '%env(FULFILMENT_RETURN_ADDRESS_POSTAL_CODE)%');
    $container->parameters()->set('fulfilment.return_address_city', '%env(FULFILMENT_RETURN_ADDRESS_CITY)%');

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Fulfilment');

    if ('test' === $container->env()) {
        // Fetched directly by type; must be public for that.
        $services->get(RequestShipmentOnOrderConfirmed::class)->public();
        $services->get(ManifestShipmentOnShipmentPrepared::class)->public();
    }
};
