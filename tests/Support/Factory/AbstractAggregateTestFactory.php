<?php

declare(strict_types=1);

namespace Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Webmozart\Assert\Assert;

/**
 * @template T of AggregateRoot
 * @template TAttributes of array<string, mixed> = array<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractAggregateTestFactory
{
    /** @var list<callable(T, TAttributes): void> */
    private array $modifiers = [];

    /** @var TAttributes|null */
    private ?array $resolvedAttributes = null;

    /**
     * @param TAttributes $attributes
     */
    final public function __construct(protected readonly array $attributes = [])
    {
    }

    /**
     * @param TAttributes $attributes
     */
    public static function new(array $attributes = []): static
    {
        /** @var static */ // @phpstan-ignore varTag.nativeType
        return new static($attributes);
    }

    /**
     * @return AggregateListTestFactory<T, TAttributes>
     */
    public function many(int $count): AggregateListTestFactory
    {
        Assert::positiveInteger($count);

        return new AggregateListTestFactory($this, $count);
    }

    /**
     * @return T
     */
    public function create(): AggregateRoot
    {
        return $this->instantiate();
    }

    /**
     * @template TName of key-of<TAttributes>
     *
     * @param TName $name
     *
     * @return TAttributes[TName]
     */
    public function attribute(string $name): mixed
    {
        return $this->resolveAttributes()[$name] ?? null;
    }

    /**
     * @return TAttributes
     */
    abstract protected function defaults(): array;

    /**
     * @return T
     */
    abstract protected function build(): AggregateRoot;

    /**
     * @return TAttributes
     */
    protected function resolveAttributes(): array
    {
        if (null === $this->resolvedAttributes) {
            /** @var TAttributes */
            $resolved = array_merge($this->defaults(), $this->attributes);
            $this->resolvedAttributes = $resolved;
        }

        return $this->resolvedAttributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function withAttributes(array $attributes): static
    {
        /** @var TAttributes */
        $merged = array_merge($this->attributes, $attributes);

        $clone = static::new($merged);
        $clone->modifiers = $this->modifiers;

        return $clone;
    }

    /**
     * @param callable(T, TAttributes): void $modifier
     */
    protected function withModifier(callable $modifier): static
    {
        $clone = clone $this;
        $clone->modifiers[] = $modifier;

        return $clone;
    }

    /**
     * @return T
     */
    private function instantiate(): AggregateRoot
    {
        $this->resolvedAttributes = null;
        $attributes = $this->resolveAttributes();
        $aggregate = $this->build();

        foreach ($this->modifiers as $modifier) {
            $modifier($aggregate, $attributes);
        }

        return $aggregate;
    }
}
