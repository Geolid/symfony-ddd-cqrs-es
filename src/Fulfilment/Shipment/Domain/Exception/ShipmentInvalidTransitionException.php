<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ShipmentStatus;

final class ShipmentInvalidTransitionException extends \DomainException
{
    public static function cannotDispatch(ShipmentStatus $current): self
    {
        return new self(\sprintf('Cannot dispatch shipment with status "%s".', $current->value));
    }

    public static function cannotMarkDelivered(ShipmentStatus $current): self
    {
        return new self(\sprintf('Cannot mark shipment as delivered with status "%s".', $current->value));
    }

    public static function cannotAssignTrackingReference(ShipmentStatus $current): self
    {
        return new self(\sprintf('Cannot assign a tracking reference to a shipment with status "%s".', $current->value));
    }

    public static function trackingReferenceAlreadyAssigned(string $current): self
    {
        return new self(\sprintf('Shipment already tracked under reference "%s".', $current));
    }
}
