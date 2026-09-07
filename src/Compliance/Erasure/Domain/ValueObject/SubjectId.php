<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Webmozart\Assert\Assert;

final readonly class SubjectId implements AggregateRootId
{
    private string $value;

    private function __construct(string $value)
    {
        Assert::uuid($value, 'An identifier must be a valid UUID, %s given.');

        $this->value = $value;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
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
