<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Money
{
    private int $value;

    private function __construct(int $value)
    {
        Assert::greaterThanEq($value, 0, 'A monetary amount cannot be negative, %s given.');

        $this->value = $value;
    }

    public static function fromCents(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function plus(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function times(int $multiplier): self
    {
        return new self($this->value * $multiplier);
    }

    public function toCents(): int
    {
        return $this->value;
    }
}
