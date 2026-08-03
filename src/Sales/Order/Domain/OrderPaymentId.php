<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class OrderPaymentId implements AggregateRootId
{
    use UuidTrait;

    private const string ORDER_NAMESPACE = '9c3e7a1d-4f52-4b6a-8e0d-1a2b3c4d5e6f';

    public static function forOrder(string $orderId): self
    {
        return new self(Uuid::uuid5(self::ORDER_NAMESPACE, $orderId)->toString());
    }
}
