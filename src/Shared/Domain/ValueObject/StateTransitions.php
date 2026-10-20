<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

/**
 * @template T of \BackedEnum
 */
final readonly class StateTransitions
{
    /**
     * @param array<string, list<T>> $transitions
     */
    public function __construct(private array $transitions)
    {
    }

    /**
     * @param T $from
     * @param T $to
     */
    public function allows(\BackedEnum $from, \BackedEnum $to): bool
    {
        return \in_array($to, $this->transitions[$from->value] ?? [], true);
    }

    /**
     * @param T $from
     * @param T $to
     */
    public function reaches(\BackedEnum $from, \BackedEnum $to): bool
    {
        $visited = [];
        $pending = [$from];

        while ([] !== $pending) {
            $current = array_pop($pending);

            if ($current === $to) {
                return true;
            }

            if (isset($visited[$current->value])) {
                continue;
            }

            $visited[$current->value] = true;

            foreach ($this->transitions[$current->value] ?? [] as $next) {
                $pending[] = $next;
            }
        }

        return false;
    }
}
