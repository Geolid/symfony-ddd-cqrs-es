<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain;

use Webmozart\Assert\Assert;

final readonly class TrackingReference
{
    private string $value;

    private function __construct(string $value)
    {
        Assert::notEmpty($value, 'A tracking reference cannot be empty, %s given.');
        Assert::maxLength($value, 64, 'A tracking reference cannot exceed %2$d characters, %s given.');

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
