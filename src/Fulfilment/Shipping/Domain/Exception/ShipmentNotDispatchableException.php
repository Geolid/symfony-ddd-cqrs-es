<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Exception;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;

final class ShipmentNotDispatchableException extends \DomainException
{
    public static function cannotDispatch(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Shipment "%s" cannot be dispatched while in state "%s".', $id->toString(), $current->value));
    }
}
