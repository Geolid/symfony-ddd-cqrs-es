<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ShipmentReconciler implements ShipmentReconcilerInterface
{
    /**
     * @param iterable<ShipmentStatusReconcilerInterface> $reconcilers
     */
    public function __construct(private iterable $reconcilers)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, ShipmentStatus $status, string $trackingNumber): bool
    {
        foreach ($this->reconcilers as $reconciler) {
            if ($reconciler->supports($status)) {
                return $reconciler->reconcile($id, $trackingNumber);
            }
        }

        throw UnsupportedShipmentStatusException::forStatus($status);
    }
}
