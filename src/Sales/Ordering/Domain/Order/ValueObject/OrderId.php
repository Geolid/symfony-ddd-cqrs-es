<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class OrderId implements AggregateRootId
{
    use UuidTrait;

    private const string CART_NAMESPACE = '2f6b8d4a-9c3e-4f1a-8b6d-5e7c9a0d1f3b';

    public static function forCart(string $cartId): self
    {
        return new self(Uuid::uuid5(self::CART_NAMESPACE, $cartId)->toString());
    }
}
