<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipping\Application\ShipmentStatus;
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
     * @throws UnsupportedShipmentStatusException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, ShipmentStatus $status, string $trackingNumber): void
    {
        foreach ($this->reconcilers as $reconciler) {
            if ($reconciler->supports($status)) {
                $reconciler->reconcile($id, $trackingNumber);

                return;
            }
        }

        throw UnsupportedShipmentStatusException::forStatus($status);
    }
}
