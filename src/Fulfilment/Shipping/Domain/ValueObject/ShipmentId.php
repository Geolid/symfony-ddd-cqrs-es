<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class ShipmentId implements AggregateRootId
{
    use UuidTrait;

    private const string ORDER_NAMESPACE = 'e6f0a1c2-7b3d-4e5f-9a0b-1c2d3e4f5a6b';

    public static function forOrder(string $orderId): self
    {
        return new self(Uuid::uuid5(self::ORDER_NAMESPACE, $orderId)->toString());
    }
}
