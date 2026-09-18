<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Fulfilment\Shipping\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipping\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThresholdHandler;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipping\Application\Warehouse\WarehouseAddressProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fulfilment.warehouse_address', [
        'recipientName' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_RECIPIENT_NAME)%',
        'address' => [
            'street' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_STREET)%',
            'postalCode' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_POSTAL_CODE)%',
            'city' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_CITY)%',
            'countryCode' => '%env(FULFILMENT_WAREHOUSE_ADDRESS_COUNTRY_CODE)%',
        ],
    ]);
    $container->parameters()->set('fulfilment.shipping.reconciliation_threshold_hours', 48);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ShipmentStatusReconcilerInterface::class)->tag('fulfilment.shipping.status_reconciler');

    BoundedContextServiceLoader::load($services, 'Fulfilment\\Shipping');

    $services->get(ShipmentReconciler::class)->arg('$reconcilers', tagged_iterator('fulfilment.shipping.status_reconciler'));

    $services->get(ListShipmentsPastReconciliationThresholdHandler::class)->arg('$thresholdHours', '%fulfilment.shipping.reconciliation_threshold_hours%');

    $services->get(WarehouseAddressProvider::class)->arg('$address', '%fulfilment.warehouse_address%');

    if ('test' === $container->env()) {
        // Fetched directly by type; must be public for that.
        $services->get(RequestShipmentOnOrderConfirmed::class)->public();
    }
};
