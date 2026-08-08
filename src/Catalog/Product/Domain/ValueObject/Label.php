<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Label
{
    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::notEmpty($value, 'A product label cannot be empty, %s given.');
        Assert::maxLength($value, 255, 'A product label cannot exceed 255 characters, %s given.');

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
