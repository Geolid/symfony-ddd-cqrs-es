<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

use Shared\Application\Port\DrivingPort;

#[DrivingPort]
interface ShipmentReconcilerInterface
{
    /**
     * @return bool whether the carrier reported a transition, and it was applied
     */
    public function reconcile(string $id, string $status, ?string $trackingReference, ?string $returnTrackingReference): bool;
}
