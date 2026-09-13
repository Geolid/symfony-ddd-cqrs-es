<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Money
{
    public int $cents;

    private function __construct(
        int $cents,
        public Currency $currency,
    ) {
        Assert::greaterThanEq($cents, 0, 'A monetary amount cannot be negative, %s given.');

        $this->cents = $cents;
    }

    public static function fromCents(int $cents, string $currency): self
    {
        Assert::oneOf($currency, Currency::values(), 'A currency must be a valid ISO 4217 code, %s given.');

        return new self($cents, Currency::from($currency));
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }

    public function plus(self $other): self
    {
        Assert::true(
            $this->currency === $other->currency,
            \sprintf('Cannot add amounts in different currencies, %s and %s given.', $this->currency->value, $other->currency->value),
        );

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function times(Quantity $quantity): self
    {
        return new self($this->cents * $quantity->value, $this->currency);
    }
}
