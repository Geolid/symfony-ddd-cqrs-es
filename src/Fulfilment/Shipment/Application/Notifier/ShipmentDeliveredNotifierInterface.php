<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

/**
 * An outbound port for the third reaction shape alongside a Processor that dispatches a
 * Command and a Reducer that enriches a read model: a side effect that leaves the system
 * entirely (an email here; could just as well be an SMS gateway, a push notification...).
 */
interface ShipmentDeliveredNotifierInterface
{
    public function notify(string $shipmentId, string $orderId, string $customerId): void;
}
