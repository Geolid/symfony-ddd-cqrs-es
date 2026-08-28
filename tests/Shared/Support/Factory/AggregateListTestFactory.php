<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

/**
 * @template T of AggregateRoot
 * @template TAttributes of array<string, mixed> = array<string, mixed>
 */
final readonly class AggregateListTestFactory
{
    /**
     * @param AbstractAggregateTestFactory<T, TAttributes> $factory
     * @param int<1, max>                                  $count
     */
    public function __construct(
        private AbstractAggregateTestFactory $factory,
        private int $count,
    ) {
    }

    /**
     * @return list<T>
     */
    public function create(): array
    {
        return array_map($this->factory->create(...), range(1, $this->count));
    }
}
