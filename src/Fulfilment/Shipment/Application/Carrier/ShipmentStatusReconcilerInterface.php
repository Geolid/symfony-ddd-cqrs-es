<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

use Fulfilment\Shipment\Application\Status\ShipmentStatus;

interface ShipmentStatusReconcilerInterface
{
    public function supports(ShipmentStatus $status): bool;

    /**
     * @return bool whether the carrier reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
