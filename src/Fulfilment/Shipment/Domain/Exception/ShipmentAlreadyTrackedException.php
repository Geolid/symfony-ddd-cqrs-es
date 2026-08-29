<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;

final class ShipmentAlreadyTrackedException extends \DomainException
{
    public static function forReference(ShipmentId $id, string $reference): self
    {
        return new self(\sprintf('Shipment "%s" is already tracked under reference "%s".', $id->toString(), $reference));
    }
}
