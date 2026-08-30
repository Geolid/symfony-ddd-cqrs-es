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
    public function reconcile(string $id, ShipmentStatus $status, ?string $trackingReference, ?string $returnTrackingReference): bool
    {
        $reference = match ($status) {
            ShipmentStatus::RETURN_MANIFESTED, ShipmentStatus::RETURN_DISPATCHED => $returnTrackingReference,
            default => $trackingReference,
        };

        \assert(null !== $reference);

        foreach ($this->reconcilers as $reconciler) {
            if ($reconciler->supports($status)) {
                return $reconciler->reconcile($id, $reference);
            }
        }

        throw UnsupportedShipmentStatusException::forStatus($status);
    }
}
