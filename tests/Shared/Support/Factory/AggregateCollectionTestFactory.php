<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

/**
 * @template T of AggregateRoot
 */
final class AggregateCollectionTestFactory
{
    /**
     * @param AbstractAggregateTestFactory<T> $factory
     * @param int<1, max>                     $count
     */
    public function __construct(
        private readonly AbstractAggregateTestFactory $factory,
        private readonly int $count,
    ) {
    }

    /**
     * @return list<T>
     */
    public function create(): array
    {
        return array_map(fn () => $this->factory->create(), range(1, $this->count));
    }

    /**
     * @return list<T>
     */
    public function store(): array
    {
        return array_map(fn () => $this->factory->store(), range(1, $this->count));
    }
}
