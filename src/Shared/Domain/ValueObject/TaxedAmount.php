<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

final readonly class TaxedAmount
{
    private function __construct(
        public Money $excludingTax,
        public Money $taxAmount,
        public Money $includingTax,
    ) {
    }

    public static function of(Money $excludingTax, Money $taxAmount): self
    {
        return new self($excludingTax, $taxAmount, $excludingTax->plus($taxAmount));
    }

    public static function zero(Currency $currency): self
    {
        return self::of(Money::fromCents(0, $currency->value), Money::fromCents(0, $currency->value));
    }

    public function plus(self $other): self
    {
        return self::of($this->excludingTax->plus($other->excludingTax), $this->taxAmount->plus($other->taxAmount));
    }

    public function equals(self $other): bool
    {
        return $this->excludingTax->equals($other->excludingTax)
            && $this->taxAmount->equals($other->taxAmount)
            && $this->includingTax->equals($other->includingTax);
    }
}
