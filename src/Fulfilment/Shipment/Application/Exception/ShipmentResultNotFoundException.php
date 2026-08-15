<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ShipmentResultNotFoundException extends ResultNotFoundException
{
    public static function forTrackingReference(string $trackingReference): self
    {
        return new self(\sprintf('Shipment not found for criteria %s.', json_encode(['trackingReference' => $trackingReference], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }
}
