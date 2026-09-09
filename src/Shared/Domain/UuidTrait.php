<?php

declare(strict_types=1);

namespace Shared\Domain;

use Webmozart\Assert\Assert;

trait UuidTrait
{
    /** @var non-empty-string */
    private readonly string $value;

    protected function __construct(string $value)
    {
        Assert::uuid($value, 'An identifier must be a valid UUID, %s given.');

        /** @var non-empty-string $value */
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

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
