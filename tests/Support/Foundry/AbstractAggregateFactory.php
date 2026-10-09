<?php

declare(strict_types=1);

namespace Support\Foundry;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Support\ClockSequence;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;

/**
 * SPIKE.
 *
 * @template T of AggregateRoot
 * @template TInputs of array<string, mixed>
 *
 * @extends ObjectFactory<T>
 */
abstract class AbstractAggregateFactory extends ObjectFactory
{
    /** @var \WeakMap<AggregateRoot, array<string, mixed>>|null */
    private static ?\WeakMap $inputs = null; // @phpstan-ignore property.readOnlyByPhpDocDefaultValue

    /**
     * What the aggregate was built from: every attribute resolved for its creation, transitions' ones included.
     *
     * @return TInputs
     */
    final public static function inputs(AggregateRoot $aggregate): array
    {
        /* @phpstan-ignore return.type */
        return self::$inputs?->offsetExists($aggregate) ? self::$inputs[$aggregate] : throw new \LogicException('Aggregate was not built through an aggregate factory.');
    }

    /**
     * A fresh draw of one attribute, with no aggregate built — the same generator `create()` uses.
     *
     * @template TName of key-of<TInputs>
     *
     * @param TName $name
     *
     * @return TInputs[TName]
     */
    final public static function sample(string $name): mixed
    {
        if ('id' === $name) {
            throw new \LogicException('The aggregate root id is never read bare via sample() — call the id Value Object\'s own named factory (::fromString()/::forX()) directly instead.');
        }

        /** @var array<string, mixed> $defaults */
        $defaults = self::new()->defaults();

        $value = \array_key_exists($name, $defaults) ? $defaults[$name] : throw new \OutOfBoundsException(\sprintf('"%s" is not an attribute of %s.', $name, static::class));

        return $value instanceof Factory ? $value->create() : $value;
    }

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
            ->instantiateWith(static fn (array $parameters): AggregateRoot => static::build($parameters)) // @phpstan-ignore argument.type
            ->afterInstantiate(static function (AggregateRoot $aggregate, array $parameters): void {
                self::$inputs ??= new \WeakMap();
                self::$inputs[$aggregate] = $parameters;
            }, 100);
    }
}
