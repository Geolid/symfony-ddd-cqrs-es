<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\ValueObject;

use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shared\Domain\ValueObject\TaxedAmount;

final readonly class OrderItem
{
    private function __construct(
        public Product $product,
        public Quantity $quantity,
        public Money $taxAmount,
    ) {
    }

    public static function of(Product $product, Quantity $quantity, Money $taxAmount): self
    {
        return new self($product, $quantity, $taxAmount);
    }

    public function total(): Money
    {
        return $this->product->price->times($this->quantity);
    }

    public function taxedTotal(): TaxedAmount
    {
        return TaxedAmount::of($this->total(), $this->taxAmount);
    }

    public function equals(self $other): bool
    {
        return $this->product->equals($other->product)
            && $this->quantity->equals($other->quantity)
            && $this->taxAmount->equals($other->taxAmount);
    }
}
