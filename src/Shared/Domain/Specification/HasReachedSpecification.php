<?php

declare(strict_types=1);

namespace Shared\Domain\Specification;

/**
 * @template T of \BackedEnum
 */
final readonly class HasReachedSpecification
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
        return $this->reaches($this->target, $candidate);
    }

    private function reaches(\BackedEnum $from, \BackedEnum $candidate): bool
    {
        if ($from === $candidate) {
            return true;
        }

        foreach ($this->transitions[$from->value] ?? [] as $next) {
            if ($this->reaches($next, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
