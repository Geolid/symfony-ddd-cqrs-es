<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

interface ShipmentDeliveredNotifierInterface
{
    public function notify(ShipmentDeliveredNotification $notification): void;
}
