<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Webmozart\Assert\Assert;

final readonly class Money
{
    private function __construct(private int $amountInCents)
    {
        Assert::greaterThanEq($amountInCents, 0, 'A monetary amount cannot be negative, %s given.');
    }

    public static function fromCents(int $amountInCents): self
    {
        return new self($amountInCents);
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents;
    }

    public function plus(self $other): self
    {
        return new self($this->amountInCents + $other->amountInCents);
    }

    public function times(int $multiplier): self
    {
        return new self($this->amountInCents * $multiplier);
    }

    public function toCents(): int
    {
        return $this->amountInCents;
    }
}
