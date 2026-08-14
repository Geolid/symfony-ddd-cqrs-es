<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\ListedProduct;

use Shared\Application\Result\ResultInterface;

final readonly class ListedProductResult implements ResultInterface
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitAmountInCents,
    ) {
    }
}
