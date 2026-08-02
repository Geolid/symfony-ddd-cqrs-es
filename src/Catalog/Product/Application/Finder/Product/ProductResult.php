<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

use Shared\Application\Query\Result\ResultInterface;

final readonly class ProductResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
        public bool $delisted,
    ) {
    }
}
