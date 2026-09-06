<?php

declare(strict_types=1);

namespace Shared\Domain\Specification;

use Webmozart\Assert\Assert;

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
        Assert::false($this->hasCycle(), 'The transitions graph must be acyclic.');
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

    private function hasCycle(): bool
    {
        /** @var array<string, bool> $status */
        $status = [];

        return array_any(
            array_keys($this->transitions),
            function (int|string $start) use (&$status): bool {
                return !isset($status[$start]) && $this->visit((string) $start, $status);
            },
        );
    }

    /**
     * @param array<string, bool> $status false = in progress, true = fully explored
     */
    private function visit(string $value, array &$status): bool
    {
        $status[$value] = false;

        foreach ($this->transitions[$value] ?? [] as $next) {
            $nextValue = (string) $next->value;

            if (isset($status[$nextValue])) {
                if (false === $status[$nextValue]) {
                    return true;
                }
            } elseif ($this->visit($nextValue, $status)) {
                return true;
            }
        }

        $status[$value] = true;

        return false;
    }
}
