<?php

declare(strict_types=1);

namespace Ordering\Order\Domain;

use Webmozart\Assert\Assert;

/**
 * A minimal Money value object, in the smallest currency unit (cents), kept local to Ordering
 * rather than promoted to Shared — it isn't (yet) needed by another Bounded Context, and
 * DDD/CQRS/ES doesn't require a shared kernel to grow speculatively.
 */
final readonly class Money
{
    private function __construct(private int $amountInCents)
    {
        Assert::greaterThanEq($amountInCents, 0, 'A monetary amount cannot be negative, %s given.');
    }

    public static function fromCents(int $amountInCents): self
    {
        return new self($amountInCents);
    }

    public function toCents(): int
    {
        return $this->amountInCents;
    }
}
