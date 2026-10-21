<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ShipmentReconcilerInterface
{
    public function reconcile(string $id, ShipmentStatus $status, string $trackingNumber): void;
}
