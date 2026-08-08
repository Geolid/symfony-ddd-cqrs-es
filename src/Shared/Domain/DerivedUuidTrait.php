<?php

declare(strict_types=1);

namespace Shared\Domain;

use Webmozart\Assert\Assert;

trait DerivedUuidTrait
{
    private readonly string $value;

    protected function __construct(string $value)
    {
        Assert::uuid($value, 'An identifier must be a valid UUID, %s given.');

        $this->value = $value;
    }

    public static function fromString(string $id): static
    {
        return new static($id);
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
