<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Mailing;

final readonly class ShipmentDeliveredNotification
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $customerId,
    ) {
    }
}
