<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

interface ShipmentCancellationRejectedNotifierInterface
{
    public function notify(ShipmentCancellationRejectedNotification $notification): void;
}
