<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Money
{
    public int $cents;

    private function __construct(int $cents)
    {
        Assert::greaterThanEq($cents, 0, 'A monetary amount cannot be negative, %s given.');

        $this->cents = $cents;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function times(int $multiplier): self
    {
        return new self($this->cents * $multiplier);
    }
}
