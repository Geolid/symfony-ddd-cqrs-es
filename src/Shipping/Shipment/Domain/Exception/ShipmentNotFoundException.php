<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain\Exception;

use Shipping\Shipment\Domain\ShipmentId;

final class ShipmentNotFoundException extends \DomainException
{
    public static function forId(ShipmentId $id): self
    {
        return new self(\sprintf('Shipment with ID "%s" not found.', $id->toString()));
    }
}
