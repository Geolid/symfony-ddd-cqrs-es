<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummaryLine;

use Shared\Application\Result\ResultInterface;

final readonly class OrderSummaryLineResult implements ResultInterface
{
    public function __construct(
        public string $orderId,
        public string $label,
        public int $quantity,
        public int $unitAmountInCents,
    ) {
    }
}
