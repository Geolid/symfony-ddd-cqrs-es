<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Fulfilment\Shipment\Application\Carrier\ShipmentReconciler;
use Fulfilment\Shipment\Application\Carrier\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThresholdHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fulfilment.return_address_recipient', '%env(FULFILMENT_RETURN_ADDRESS_RECIPIENT)%');
    $container->parameters()->set('fulfilment.return_address_street', '%env(FULFILMENT_RETURN_ADDRESS_STREET)%');
    $container->parameters()->set('fulfilment.return_address_postal_code', '%env(FULFILMENT_RETURN_ADDRESS_POSTAL_CODE)%');
    $container->parameters()->set('fulfilment.return_address_city', '%env(FULFILMENT_RETURN_ADDRESS_CITY)%');
    $container->parameters()->set('fulfilment.return_address_country_code', '%env(FULFILMENT_RETURN_ADDRESS_COUNTRY_CODE)%');
    $container->parameters()->set('fulfilment.shipment.reconciliation_threshold_hours', 48);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ShipmentStatusReconcilerInterface::class)->tag('fulfilment.shipment_status_reconciler');

    SubdomainServiceLoader::load($services, 'Fulfilment');

    $services->get(ShipmentReconciler::class)->arg('$reconcilers', tagged_iterator('fulfilment.shipment_status_reconciler'));

    $services->get(ListShipmentsPastReconciliationThresholdHandler::class)->arg('$thresholdHours', '%fulfilment.shipment.reconciliation_threshold_hours%');

    if ('test' === $container->env()) {
        // Fetched directly by type; must be public for that.
        $services->get(RequestShipmentOnOrderConfirmed::class)->public();
        $services->get(ManifestShipmentOnShipmentPrepared::class)->public();
    }
};
