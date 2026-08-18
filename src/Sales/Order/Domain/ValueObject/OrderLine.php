<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

use Shared\Domain\ValueObject\Money;
use Webmozart\Assert\Assert;

final readonly class OrderLine
{
    public int $quantity;

    private function __construct(
        public Product $product,
        int $quantity,
    ) {
        Assert::positiveInteger($quantity, 'An order line quantity must be positive, %s given.');
        $this->quantity = $quantity;
    }

    public static function of(Product $product, int $quantity): self
    {
        return new self($product, $quantity);
    }

    public function total(): Money
    {
        return $this->product->price->times($this->quantity);
    }

    public function equals(self $other): bool
    {
        return $this->product->equals($other->product)
            && $this->quantity === $other->quantity;
    }
}
