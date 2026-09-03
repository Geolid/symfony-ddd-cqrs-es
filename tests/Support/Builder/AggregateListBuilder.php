<?php

declare(strict_types=1);

namespace Support\Builder;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

/**
 * @template T of AggregateRoot
 * @template TAttributes of array<string, mixed>
 */
final readonly class AggregateListBuilder
{
    /**
     * @param AbstractAggregateBuilder<T, TAttributes> $factory
     * @param int<1, max>                              $count
     */
    public function __construct(
        private AbstractAggregateBuilder $factory,
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
