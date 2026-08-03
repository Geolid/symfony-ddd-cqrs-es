<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class ShipmentId implements AggregateRootId
{
    use UuidTrait;

    private const string ORDER_NAMESPACE = '6f9b1a52-3c7d-4e08-9a41-2b5c8d0e7f36';

    public static function forOrder(string $orderId): self
    {
        return new self(Uuid::uuid5(self::ORDER_NAMESPACE, $orderId)->toString());
    }
}
