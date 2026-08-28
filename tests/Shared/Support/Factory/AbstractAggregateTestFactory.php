<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Faker\Generator;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Tools\Faker\SeededFaker;
use Webmozart\Assert\Assert;

/**
 * @template T of AggregateRoot
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractAggregateTestFactory
{
    /** @var list<callable(T): void> */
    private array $modifiers = [];

    /**
     * @param array<string, mixed> $attributes
     */
    final public function __construct(protected readonly array $attributes = [])
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function new(array $attributes = []): static
    {
        /** @var static<T> */ // @phpstan-ignore varTag.nativeType
        return new static($attributes);
    }

    /**
     * @return AggregateListTestFactory<T>
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
     * @return array<string, mixed>
     */
    abstract protected function defaults(): array;

    /**
     * @param array<string, mixed> $attributes
     *
     * @return T
     */
    abstract protected function build(array $attributes): AggregateRoot;

    /**
     * @param array<string, mixed> $attributes
     */
    protected function withAttributes(array $attributes): static
    {
        $clone = static::new($attributes);
        $clone->modifiers = $this->modifiers;

        return $clone;
    }

    /**
     * @param callable(T): void $modifier
     */
    protected function withModifier(callable $modifier): static
    {
        $clone = clone $this;
        $clone->modifiers[] = $modifier;

        return $clone;
    }

    protected static function faker(): Generator
    {
        return SeededFaker::get();
    }

    /**
     * @return T
     */
    private function instantiate(): AggregateRoot
    {
        $attributes = array_merge($this->defaults(), $this->attributes);
        $aggregate = $this->build($attributes);

        foreach ($this->modifiers as $modifier) {
            $modifier($aggregate);
        }

        return $aggregate;
    }
}
