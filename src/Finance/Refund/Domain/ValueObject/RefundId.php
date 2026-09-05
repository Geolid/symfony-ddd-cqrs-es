<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\DerivedUuidTrait;

final readonly class RefundId implements AggregateRootId
{
    use DerivedUuidTrait;

    private const string PAYMENT_NAMESPACE = '6e1a9c2d-3b47-4f8a-9c1d-5e2b7a8f4d60';

    public static function forPayment(string $paymentId): self
    {
        return new self(Uuid::uuid5(self::PAYMENT_NAMESPACE, $paymentId)->toString());
    }
}
