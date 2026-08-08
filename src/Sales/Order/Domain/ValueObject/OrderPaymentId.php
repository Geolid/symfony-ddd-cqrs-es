<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\DerivedUuidTrait;

final readonly class OrderPaymentId implements AggregateRootId
{
    use DerivedUuidTrait;

    private const string ORDER_NAMESPACE = '9c3e7a1d-4f52-4b6a-8e0d-1a2b3c4d5e6f';

    public static function forOrder(string $orderId): self
    {
        return new self(Uuid::uuid5(self::ORDER_NAMESPACE, $orderId)->toString());
    }
}
