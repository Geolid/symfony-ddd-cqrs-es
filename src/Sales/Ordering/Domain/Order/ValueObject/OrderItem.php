<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\ValueObject;

use Shared\Domain\ValueObject\Money;

final readonly class OrderItem
{
    private function __construct(
        public Product $product,
        public Quantity $quantity,
    ) {
    }

    public static function of(Product $product, Quantity $quantity): self
    {
        return new self($product, $quantity);
    }

    public function total(): Money
    {
        return $this->product->price->times($this->quantity->value);
    }

    public function equals(self $other): bool
    {
        return $this->product->equals($other->product)
            && $this->quantity->equals($other->quantity);
    }
}
