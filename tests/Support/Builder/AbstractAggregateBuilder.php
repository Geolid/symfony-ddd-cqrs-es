<?php

declare(strict_types=1);

namespace Support\Builder;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Support\ClockSequence;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Webmozart\Assert\Assert;

/**
 * @template T of AggregateRoot
 * @template TAttributes of array<string, mixed>
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class AbstractAggregateBuilder implements \ArrayAccess
{
    /** @var list<callable(T, static): void> */
    private array $modifiers = [];

    /** @var array<string, mixed> */
    private array $generatedAttributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    final private function __construct(private readonly array $attributes = [])
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function new(array $attributes = []): static
    {
        /* @phpstan-ignore return.type */
        return new static($attributes);
    }

    /**
     * @return AggregateListBuilder<T, TAttributes>
     */
    public function many(int $count): AggregateListBuilder
    {
        Assert::positiveInteger($count);

        return new AggregateListBuilder($this, $count);
    }

    /**
     * build() and every modifier run under one clock ticked once and frozen — a date generator
     * only ever needs Clock::get()->now(), never a cross-reference to another attribute, to stay
     * coherent with every other date resolved in this same call. Restored even if this throws.
     *
     * @return T of AggregateRoot
     */
    public function create(): AggregateRoot
    {
        $this->generatedAttributes = [];

        $previousClock = Clock::get();
        Clock::set(new MockClock(ClockSequence::next()));

        try {
            $aggregate = $this->build();

            foreach ($this->modifiers as $modifier) {
                $modifier($aggregate, $this);
            }
        } finally {
            Clock::set($previousClock);
        }

        return $aggregate;
    }

    /**
     * A fresh pull straight from the generator — never cached. For a value this instance
     * doesn't need built/persisted, only borrowed on the fly (a Command argument, an id to
     * query a Finder with). No instance backs this call, so a generator deriving from another
     * key falls back to sampling that other key independently instead of reading it off one.
     *
     * @template TName of key-of<TAttributes>
     *
     * @param TName $name
     *
     * @return TAttributes[TName]
     */
    public static function sample(string $name): mixed
    {
        return (static::defaults()[$name])(null);
    }

    public function offsetExists(mixed $offset): bool
    {
        Assert::string($offset);

        return \array_key_exists($offset, $this->attributes)
            || \array_key_exists($offset, $this->generatedAttributes)
            || \array_key_exists($offset, static::defaults());
    }

    /**
     * @template TName of key-of<TAttributes>
     *
     * @param TName $offset
     *
     * @return TAttributes[TName]
     */
    public function offsetGet(mixed $offset): mixed
    {
        Assert::string($offset);

        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Builder attributes are read-only. Use "withAttributes()" to override values.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Builder attributes are read-only.');
    }

    /**
     * @return T
     */
    abstract protected function build(): AggregateRoot;

    /**
     * A date generator reads only Clock::get()->now() — create() freezes it once for the whole
     * call, so every date resolved during that call is coherent by construction. A generator
     * for a key derived from another takes the current instance, or `null` when called through
     * `sample()` with no instance to read from — an ordinary generator with no such dependency
     * simply ignores the parameter.
     *
     * @return array<key-of<TAttributes>, callable(?static): mixed>
     */
    abstract protected static function defaults(): array;

    protected function withAttributes(mixed ...$attributes): static
    {
        /** @var array<string, mixed> $attributes */
        return clone ($this, ['attributes' => [...$this->attributes, ...$attributes]]);
    }

    /**
     * @param callable(T, static): void $modifier
     */
    protected function withModifier(callable $modifier): static
    {
        return clone ($this, ['modifiers' => [...$this->modifiers, $modifier]]);
    }

    /**
     * Caches a generated value on first read, so every later read (and every modifier) sees
     * the exact same one. The generator receives this same instance, so a key derived from
     * another reads that other key off it — through the exact same cache — instead of
     * drawing its own independent value.
     *
     * @template TName of key-of<TAttributes>
     *
     * @param TName $name
     *
     * @return TAttributes[TName]
     */
    private function get(string $name): mixed
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        if (\array_key_exists($name, $this->generatedAttributes)) {
            return $this->generatedAttributes[$name];
        }

        return $this->generatedAttributes[$name] = (static::defaults()[$name])($this);
    }
}
