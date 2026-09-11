<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class PaymentId implements AggregateRootId
{
    use UuidTrait;

    private const string CHECKOUT_SESSION_NAMESPACE = '7a1e9c3d-4b6f-4a2e-9d8c-3f5b6a7e8c9d';

    public static function forCheckoutSession(string $checkoutSessionId): self
    {
        return new self(Uuid::uuid5(self::CHECKOUT_SESSION_NAMESPACE, $checkoutSessionId)->toString());
    }
}
