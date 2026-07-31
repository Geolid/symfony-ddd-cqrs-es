<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

final readonly class ShipmentDeliveredNotification
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $customerId,
        public string $customerAddress,
    ) {
    }
}
