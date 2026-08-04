<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderLine;

use Shared\Application\Query\Result\ResultInterface;

final readonly class OrderLineResult implements ResultInterface
{
    public function __construct(
        public string $orderId,
        public string $label,
        public int $quantity,
        public int $unitAmountInCents,
    ) {
    }
}
