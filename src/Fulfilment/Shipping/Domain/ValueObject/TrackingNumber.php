<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class TrackingNumber
{
    public const int MAX_LENGTH = 64;

    public string $value;

    private function __construct(string $value)
    {
        $value = trim($value);

        Assert::notEmpty($value, 'A tracking reference cannot be empty, %s given.');
        Assert::maxLength($value, self::MAX_LENGTH, 'A tracking reference cannot exceed %2$d characters, %s given.');

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
}
