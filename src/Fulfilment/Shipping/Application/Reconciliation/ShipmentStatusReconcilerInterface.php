<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\ShipmentStatus;

interface ShipmentStatusReconcilerInterface
{
    public function supports(ShipmentStatus $status): bool;

    public function reconcile(string $id, string $reference): void;
}
