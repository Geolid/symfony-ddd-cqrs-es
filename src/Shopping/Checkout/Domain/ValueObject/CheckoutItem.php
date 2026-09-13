<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\ValueObject;

use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shared\Domain\ValueObject\TaxedAmount;
use Webmozart\Assert\Assert;

final readonly class CheckoutItem
{
    public string $productId;

    private function __construct(
        string $productId,
        public Label $label,
        public Money $unitPrice,
        public Quantity $quantity,
        public TaxRate $taxRate,
    ) {
        Assert::stringNotEmpty($productId, 'A product id cannot be empty, %s given.');

        $this->productId = $productId;
    }

    public static function of(string $productId, Label $label, Money $unitPrice, Quantity $quantity, TaxRate $taxRate): self
    {
        return new self($productId, $label, $unitPrice, $quantity, $taxRate);
    }

    public function total(): TaxedAmount
    {
        $excludingTax = $this->unitPrice->times($this->quantity);
        $taxAmount = Money::fromCents(
            (int) round($excludingTax->cents * $this->taxRate->basisPoints / 10_000),
            $excludingTax->currency->value,
        );

        return TaxedAmount::of($excludingTax, $taxAmount);
    }

    public function equals(self $other): bool
    {
        return $this->productId === $other->productId
            && $this->label->equals($other->label)
            && $this->unitPrice->equals($other->unitPrice)
            && $this->quantity->equals($other->quantity)
            && $this->taxRate->equals($other->taxRate);
    }
}
