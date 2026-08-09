<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Label
{
    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::notEmpty($value, 'A label cannot be empty, %s given.');
        Assert::maxLength($value, 255, 'A label cannot exceed %2$d characters, %s given.');

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
