<?php

declare(strict_types=1);

namespace Support\Foundry;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Support\ClockSequence;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @template T of AggregateRoot
 * @template TInputs of array<string, mixed>
 *
 * @extends ObjectFactory<T>
 */
abstract class AbstractAggregateFactory extends ObjectFactory
{
    /**
     * The whole create() — defaults, instantiation, every transition — runs under one clock ticked once and frozen:
     * every date resolved during it stays coherent, and a later create() always lands after an earlier one.
     */
    public function create(callable|array $attributes = []): object
    {
        $previousClock = Clock::get();
        Clock::set(new MockClock(ClockSequence::next()));

        try {
            return parent::create($attributes);
        } finally {
            Clock::set($previousClock);
        }
    }

    /**
     * @param callable(T, TInputs): mixed $transition
     */
    final protected function transition(callable $transition): static
    {
        /* @phpstan-ignore argument.type */
        return $this->afterInstantiate($transition);
    }

    /**
     * @param TInputs $parameters
     *
     * @return T
     */
    abstract protected static function build(array $parameters): AggregateRoot;

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(static fn (array $parameters): AggregateRoot => static::build($parameters)); // @phpstan-ignore argument.type
    }
}
