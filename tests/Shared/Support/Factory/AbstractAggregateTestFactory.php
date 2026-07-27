<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Faker\Factory as Faker;
use Faker\Generator;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Test\IncrementalRamseyUuidFactory;
use Ramsey\Uuid\Uuid;
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

    private int $count = 1;

    private bool $useIncrementalIds = true;

    private static ?IncrementalRamseyUuidFactory $incrementalFactory = null;

    /** @var array<string, Generator> */
    private static array $fakers = [];

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
     * @param array<string, mixed> $attributes
     *
     * @return T
     */
    public static function createOne(array $attributes = []): AggregateRoot
    {
        return self::new($attributes)->create();
    }

    /**
     * @param int<1, max>          $count
     * @param array<string, mixed> $attributes
     *
     * @return list<T>
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        return self::new($attributes)->many($count)->createList();
    }

    public function many(int $count): static
    {
        Assert::positiveInteger($count);

        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    /**
     * @return T
     */
    public function create(): AggregateRoot
    {
        $factory = fn () => $this->instantiate();

        if ($this->useIncrementalIds) {
            return $this->wrapWithIncrementalIds($factory);
        }

        return $factory();
    }

    /**
     * @return list<T>
     */
    public function createList(): array
    {
        return array_map(fn () => $this->create(), range(1, $this->count));
    }

    public function withIncrementalIds(): static
    {
        $clone = clone $this;
        $clone->useIncrementalIds = true;

        return $clone;
    }

    public function withoutIncrementalIds(): static
    {
        $clone = clone $this;
        $clone->useIncrementalIds = false;

        return $clone;
    }

    public static function resetSequences(): void
    {
        self::$incrementalFactory?->reset();
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
     * @param callable(T): void $modifier
     */
    protected function withModifier(callable $modifier): static
    {
        $clone = clone $this;
        $clone->modifiers[] = $modifier;

        return $clone;
    }

    protected static function faker(string $locale = Faker::DEFAULT_LOCALE): Generator
    {
        return self::$fakers[$locale] ??= Faker::create($locale);
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

    /**
     * @template R
     *
     * @param callable(): R $callback
     *
     * @return R
     */
    private function wrapWithIncrementalIds(callable $callback): mixed
    {
        $original = Uuid::getFactory();
        self::$incrementalFactory ??= new IncrementalRamseyUuidFactory();

        Uuid::setFactory(self::$incrementalFactory);

        try {
            return $callback();
        } finally {
            Uuid::setFactory($original);
        }
    }
}
