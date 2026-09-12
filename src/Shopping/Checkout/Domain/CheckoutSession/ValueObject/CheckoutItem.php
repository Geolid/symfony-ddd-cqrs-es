<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\ValueObject;

use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Webmozart\Assert\Assert;

final readonly class CheckoutItem
{
    public string $productId;

    private function __construct(
        string $productId,
        public Label $label,
        public Money $unitPrice,
        public Quantity $quantity,
    ) {
        Assert::stringNotEmpty($productId, 'A product id cannot be empty, %s given.');

        $this->productId = $productId;
    }

    public static function of(string $productId, Label $label, Money $unitPrice, Quantity $quantity): self
    {
        return new self($productId, $label, $unitPrice, $quantity);
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->times($this->quantity->value);
    }

    public function equals(self $other): bool
    {
        return $this->productId === $other->productId
            && $this->label->equals($other->label)
            && $this->unitPrice->equals($other->unitPrice)
            && $this->quantity->equals($other->quantity);
    }
}
