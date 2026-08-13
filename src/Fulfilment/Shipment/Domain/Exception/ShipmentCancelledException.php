<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;

final class ShipmentCancelledException extends \DomainException
{
    public static function forId(ShipmentId $id): self
    {
        return new self(\sprintf('Shipment with ID "%s" is cancelled.', $id->toString()));
    }
}
