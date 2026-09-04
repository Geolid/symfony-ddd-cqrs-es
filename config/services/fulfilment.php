<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThresholdHandler;
use Fulfilment\Shipment\Application\Warehouse\WarehouseAddressProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fulfilment.warehouse_address', [
        'recipientName' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_RECIPIENT_NAME)%',
        'street' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_STREET)%',
        'postalCode' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_POSTAL_CODE)%',
        'city' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_CITY)%',
        'countryCode' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_COUNTRY_CODE)%',
    ]);
    $container->parameters()->set('fulfilment.shipment.reconciliation_threshold_hours', 48);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ShipmentStatusReconcilerInterface::class)->tag('fulfilment.shipment.status_reconciler');

    SubdomainServiceLoader::load($services, 'Fulfilment');

    $services->get(ShipmentReconciler::class)->arg('$reconcilers', tagged_iterator('fulfilment.shipment.status_reconciler'));

    $services->get(ListShipmentsPastReconciliationThresholdHandler::class)->arg('$thresholdHours', '%fulfilment.shipment.reconciliation_threshold_hours%');

    $services->get(WarehouseAddressProvider::class)->arg('$address', '%fulfilment.warehouse_address%');

    if ('test' === $container->env()) {
        // Fetched directly by type; must be public for that.
        $services->get(RequestShipmentOnOrderConfirmed::class)->public();
        $services->get(ManifestShipmentOnShipmentPrepared::class)->public();
    }
};
