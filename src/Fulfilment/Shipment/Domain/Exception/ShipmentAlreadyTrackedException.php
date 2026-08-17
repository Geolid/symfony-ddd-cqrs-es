<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

final class ShipmentAlreadyTrackedException extends \DomainException
{
    public static function forReference(string $reference): self
    {
        return new self(\sprintf('Shipment is already tracked under reference "%s".', $reference));
    }
}
