<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ShipmentReconcilerInterface
{
    /**
     * @throws ShipmentResultNotFoundException
     * @throws UnsupportedShipmentStatusException
     */
    public function reconcile(string $shipmentId): void;
}
