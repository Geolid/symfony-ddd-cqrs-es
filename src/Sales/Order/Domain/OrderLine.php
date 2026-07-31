<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Webmozart\Assert\Assert;

final readonly class OrderLine
{
    public string $label;

    public int $quantity;

    public Money $unitAmount;

    private function __construct(string $label, int $quantity, Money $unitAmount)
    {
        $label = trim($label);
        Assert::stringNotEmpty($label, 'An order line label cannot be empty, %s given.');
        Assert::positiveInteger($quantity, 'An order line quantity must be positive, %s given.');

        $this->label = $label;
        $this->quantity = $quantity;
        $this->unitAmount = $unitAmount;
    }

    public static function of(string $label, int $quantity, Money $unitAmount): self
    {
        return new self($label, $quantity, $unitAmount);
    }

    public function total(): Money
    {
        return $this->unitAmount->times($this->quantity);
    }

    public function equals(self $other): bool
    {
        return $this->label === $other->label
            && $this->quantity === $other->quantity
            && $this->unitAmount->equals($other->unitAmount);
    }
}
