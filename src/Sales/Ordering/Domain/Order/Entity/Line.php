<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Entity;

use Sales\Ordering\Domain\Order\ValueObject\LineId;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Sales\Ordering\Domain\Order\ValueObject\Quantity;
use Shared\Domain\ValueObject\Money;

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
