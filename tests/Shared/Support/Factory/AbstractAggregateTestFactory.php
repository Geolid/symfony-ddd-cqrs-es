<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Faker\Factory as Faker;
use Faker\Generator;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Test\IncrementalRamseyUuidFactory;
use Ramsey\Uuid\Uuid;
use Support\Helpers\KernelTestCaseHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
     * @return AggregateCollectionTestFactory<T>
     */
    public function many(int $count): AggregateCollectionTestFactory
    {
        Assert::positiveInteger($count);

        return new AggregateCollectionTestFactory($this, $count);
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
     * @return T
     */
    public function store(): AggregateRoot
    {
        $aggregate = $this->create();

        // Kernel/container statics live on KernelTestCase itself, never redeclared by any
        // subclass — this reaches whichever one the running test already booted.
        $manager = KernelTestCaseHelper::getContainer(KernelTestCase::class)
            ->get(RepositoryManager::class);
        \assert($manager instanceof RepositoryManager);

        $manager->get($aggregate::class)->save($aggregate);

        return $aggregate;
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

    public static function resetSequence(): void
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
     * @param array<string, mixed> $attributes
     */
    protected function withAttributes(array $attributes): static
    {
        $clone = static::new($attributes);
        $clone->modifiers = $this->modifiers;
        $clone->useIncrementalIds = $this->useIncrementalIds;

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
