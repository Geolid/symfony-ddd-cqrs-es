<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ShipmentNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shipment "%s" not found.', $id));
    }
}
