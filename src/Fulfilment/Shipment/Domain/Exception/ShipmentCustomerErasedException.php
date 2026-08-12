<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;

final class ShipmentCustomerErasedException extends \DomainException
{
    public static function forId(ShipmentId $id): self
    {
        return new self(\sprintf('Shipment with ID "%s" has an erased customer.', $id->toString()));
    }
}
