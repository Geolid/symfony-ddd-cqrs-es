<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Exception;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;

final class ShipmentNotDeliverableException extends \DomainException
{
    public static function cannotDeliver(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot deliver shipment "%s" with status "%s".', $id->toString(), $current->value));
    }
}
