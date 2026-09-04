<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummaryLine;

final readonly class OrderSummaryLineResult
{
    public function __construct(
        public string $orderId,
        public string $label,
        public int $quantity,
        public int $unitPriceInCents,
    ) {
    }
}
