<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

interface ShipmentDeliveredNotifierInterface
{
    public function notify(string $shipmentId, string $orderId, string $customerId): void;
}
