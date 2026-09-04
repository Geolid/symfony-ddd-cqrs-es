<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\ShipmentStatus;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ShipmentReconcilerInterface
{
    /**
     * @return bool whether the carrier reported a transition, and it was applied
     */
    public function reconcile(string $id, ShipmentStatus $status, string $trackingNumber): bool;
}
