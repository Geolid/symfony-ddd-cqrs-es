<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Fulfilment\Shipment\Application\Processor\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\Processor\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThresholdHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fulfilment.return_address_name', '%env(FULFILMENT_RETURN_ADDRESS_NAME)%');
    $container->parameters()->set('fulfilment.return_address_street', '%env(FULFILMENT_RETURN_ADDRESS_STREET)%');
    $container->parameters()->set('fulfilment.return_address_postal_code', '%env(FULFILMENT_RETURN_ADDRESS_POSTAL_CODE)%');
    $container->parameters()->set('fulfilment.return_address_city', '%env(FULFILMENT_RETURN_ADDRESS_CITY)%');
    $container->parameters()->set('fulfilment.shipment.reconciliation_threshold_hours', 48);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Fulfilment');

    $services->get(ListShipmentsPastReconciliationThresholdHandler::class)->arg('$thresholdHours', '%fulfilment.shipment.reconciliation_threshold_hours%');

    if ('test' === $container->env()) {
        // Fetched directly by type; must be public for that.
        $services->get(RequestShipmentOnOrderConfirmed::class)->public();
        $services->get(ManifestShipmentOnShipmentPrepared::class)->public();
    }
};
