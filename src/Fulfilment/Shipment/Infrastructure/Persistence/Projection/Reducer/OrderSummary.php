<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Reducer;

final readonly class OrderSummary
{
    public function __construct(
        public string $customerId,
        public int $totalAmountInCents,
    ) {
    }
}
