<?php

declare(strict_types=1);

namespace Shared\Domain\Specification;

/**
 * @template T of \BackedEnum
 */
final readonly class CanTransitionToSpecification
{
    /**
     * @param array<string, list<T>> $transitions
     */
    public function __construct(
        private array $transitions,
        private \BackedEnum $target,
    ) {
    }

    public function isSatisfiedBy(\BackedEnum $candidate): bool
    {
        return \in_array($this->target, $this->transitions[$candidate->value] ?? [], true);
    }
}
