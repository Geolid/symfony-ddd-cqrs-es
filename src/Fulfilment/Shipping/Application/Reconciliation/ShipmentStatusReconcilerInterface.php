<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\ShipmentStatus;

interface ShipmentStatusReconcilerInterface
{
    public function supports(ShipmentStatus $status): bool;

    /**
     * @return bool whether the carrier reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
