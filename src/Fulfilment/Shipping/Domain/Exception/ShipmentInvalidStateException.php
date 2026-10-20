<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Exception;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;

final class ShipmentInvalidStateException extends \DomainException
{
    public static function cannotManifest(ShipmentId $id, ShipmentState $state): self
    {
        return new self(\sprintf('Shipment "%s" cannot be manifested while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotDispatch(ShipmentId $id, ShipmentState $state): self
    {
        return new self(\sprintf('Shipment "%s" cannot be dispatched while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotDeliver(ShipmentId $id, ShipmentState $state): self
    {
        return new self(\sprintf('Shipment "%s" cannot be delivered while in state "%s".', $id->toString(), $state->value));
    }
}
