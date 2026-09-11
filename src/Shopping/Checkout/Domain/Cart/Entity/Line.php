<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Entity;

use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

final readonly class Line
{
    public function __construct(
        public LineId $id,
        public Product $product,
        public Quantity $quantity,
    ) {
    }

    public function total(): Money
    {
        return $this->product->price->times($this->quantity->value);
    }
}
