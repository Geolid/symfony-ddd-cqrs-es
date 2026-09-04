<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class ShipmentId implements AggregateRootId
{
    use UuidTrait;
}
