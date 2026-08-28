<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

interface ShipmentStatusReconcilerInterface
{
    public function supports(string $status): bool;

    /**
     * @return bool whether the carrier reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
