<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\DerivedUuidTrait;

final readonly class WithdrawalId implements AggregateRootId
{
    use DerivedUuidTrait;

    private const string ORDER_NAMESPACE = '2f6a9d3e-8b41-4c5a-9d2e-7a1c4b6f9e03';

    public static function forOrder(string $orderId): self
    {
        return new self(Uuid::uuid5(self::ORDER_NAMESPACE, $orderId)->toString());
    }
}
