<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

/**
 * @template T of \BackedEnum
 */
final readonly class StateTransitions
{
    /**
     * @param array<string, list<T>> $transitions
     */
    private function __construct(private array $transitions)
    {
        Assert::notEmpty($transitions, 'The transitions must declare at least one state.');

        foreach ($transitions as $targets) {
            Assert::isList($targets, 'The targets of a state must be a list.');
            Assert::allIsInstanceOf($targets, \BackedEnum::class);

            foreach ($targets as $target) {
                Assert::keyExists($transitions, $target->value, 'The target state %s must be declared.');
            }
        }
    }

    /**
     * @param array<string, list<T>> $transitions
     *
     * @return self<T>
     */
    public static function of(array $transitions): self
    {
        return new self($transitions);
    }

    /**
     * @param T $current
     * @param T $target
     */
    public function can(\BackedEnum $current, \BackedEnum $target): bool
    {
        return \in_array($target, $this->transitions[$current->value] ?? [], true);
    }
}
