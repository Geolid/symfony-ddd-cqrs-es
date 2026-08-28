<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class ShipmentAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shipment "%s" already exists.', $id));
    }
}
