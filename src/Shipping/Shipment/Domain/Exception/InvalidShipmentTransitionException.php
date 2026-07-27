<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain\Exception;

use Shipping\Shipment\Domain\ShipmentId;
use Shipping\Shipment\Domain\ShipmentStatus;

final class InvalidShipmentTransitionException extends \DomainException
{
    public static function forId(ShipmentId $id, ShipmentStatus $current, string $attemptedTransition): self
    {
        return new self(\sprintf(
            'Shipment with ID "%s" cannot transition via "%s" from status "%s".',
            $id->toString(),
            $attemptedTransition,
            $current->value,
        ));
    }
}
