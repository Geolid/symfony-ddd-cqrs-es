<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Shared\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Quantity
{
    private function __construct(public int $value)
    {
        Assert::positiveInteger($value, 'A quantity must be positive, %s given.');
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function plus(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
