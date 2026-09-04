<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ShipmentResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shipment "%s" not found.', $id));
    }

    public static function forTrackingNumber(string $trackingNumber): self
    {
        return new self(\sprintf('Shipment tracked under reference "%s" not found.', $trackingNumber));
    }
}
