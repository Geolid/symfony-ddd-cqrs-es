<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;

final class ShipmentInvalidTransitionException extends \DomainException
{
    public static function cannotDispatch(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot dispatch shipment with status "%s".', $current->value));
    }

    public static function cannotMarkDelivered(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot mark shipment as delivered with status "%s".', $current->value));
    }

    public static function cannotAssignTrackingReference(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot assign a tracking reference to a shipment with status "%s".', $current->value));
    }

    public static function cannotReassignTrackingReference(string $current): self
    {
        return new self(\sprintf('Shipment already tracked under reference "%s".', $current));
    }
}
