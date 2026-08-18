<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ShipmentResultNotFoundException extends ResultNotFoundException
{
    public static function forTrackingReference(string $trackingReference): self
    {
        return new self(\sprintf('Shipment tracked under reference "%s" not found.', $trackingReference));
    }

    public static function forReturnTrackingReference(string $returnTrackingReference): self
    {
        return new self(\sprintf('Shipment tracked under return reference "%s" not found.', $returnTrackingReference));
    }
}
