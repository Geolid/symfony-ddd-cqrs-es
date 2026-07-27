<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class ShipmentId implements AggregateRootId
{
    use UuidTrait;
}
