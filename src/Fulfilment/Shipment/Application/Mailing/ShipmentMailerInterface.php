<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Mailing;

interface ShipmentMailerInterface
{
    public function sendDelivered(ShipmentDeliveredNotification $notification): void;
}
