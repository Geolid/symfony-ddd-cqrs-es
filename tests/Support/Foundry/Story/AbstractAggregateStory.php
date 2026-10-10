<?php

declare(strict_types=1);

namespace Support\Foundry\Story;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Zenstruck\Foundry\Story;

abstract class AbstractAggregateStory extends Story
{
    public function __construct(private readonly RepositoryManager $repositories)
    {
    }

    final protected function store(AggregateRoot ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            $this->repositories->get($aggregate::class)->save($aggregate);
        }
    }
}
