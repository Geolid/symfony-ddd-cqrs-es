<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Webmozart\Assert\Assert;

final readonly class Product
{
    public string $id;

    private function __construct(
        string $id,
        public Label $label,
        public Money $price,
    ) {
        Assert::stringNotEmpty($id, 'A product id cannot be empty, %s given.');

        $this->id = $id;
    }

    public static function of(string $id, Label $label, Money $price): self
    {
        return new self($id, $label, $price);
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id
            && $this->label->equals($other->label)
            && $this->price->equals($other->price);
    }
}
