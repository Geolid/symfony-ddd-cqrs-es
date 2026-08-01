<?php

declare(strict_types=1);

namespace Support\Helpers;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\PhpUnit\Test\SubscriberUtilities;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Subscription\Subscriber\MetadataSubscriberAccessor;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberAccessorRepository;

trait EventSourcingTrait
{
    abstract protected function service(string $serviceId): mixed;

    protected function store(AggregateRoot $aggregate): void
    {
        $this->service(RepositoryManager::class)
            ->get($aggregate::class)
            ->save($aggregate);
    }

    protected function project(AggregateRoot ...$aggregates): void
    {
        $events = $this->extractEvents(...$aggregates);

        /** @var iterable<MetadataSubscriberAccessor<object>> $accessors */
        $accessors = $this->service(SubscriberAccessorRepository::class)->all();
        $projectors = [];

        foreach ($accessors as $accessor) {
            if ('projector' === $accessor->metadata()->group) {
                $projectors[] = $accessor->subscriber();
            }
        }

        new SubscriberUtilities($projectors)->executeRun(...$events);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $projector
     */
    protected function projectWith(string $projector, AggregateRoot ...$aggregates): void
    {
        $events = $this->extractEvents(...$aggregates);

        new SubscriberUtilities($this->service($projector))->executeRun(...$events);
    }

    /**
     * @return array<object>
     */
    private function extractEvents(AggregateRoot ...$aggregates): array
    {
        return array_merge(...array_map(static fn ($a) => $a->releaseEvents(), $aggregates));
    }
}
