<?php

declare(strict_types=1);

namespace Support\TestCase;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Store\Criteria\CriteriaBuilder;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Infrastructure\EventStore\PatchlevelIntegrationEventPublisher;

trait EventSourcingTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    abstract protected function service(string $serviceId): object;

    /**
     * Saves aggregates, synchronously triggering publishers and projectors.
     *
     * @see config/packages/patchlevel_event_sourcing.php (run_after_aggregate_save)
     */
    protected function store(AggregateRoot ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            $this->service(RepositoryManager::class)
                ->get($aggregate::class)
                ->save($aggregate);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    protected function publishedEventOf(string $eventClass): object
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof $eventClass) {
                return $event;
            }
        }

        self::fail(\sprintf('%s event not found.', $eventClass));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    protected function storedEventOf(string $eventClass, AggregateRoot&AggregateRootMetadataAware $aggregate): object
    {
        return $this->firstStoredEventIn($eventClass, $aggregate::metadata()->streamName($aggregate->aggregateRootId()->toString()));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    protected function storedIntegrationEventOf(string $eventClass, AggregateRoot&AggregateRootMetadataAware $aggregate): object
    {
        return $this->firstStoredEventIn($eventClass, PatchlevelIntegrationEventPublisher::streamNameOf($aggregate::class, $aggregate->aggregateRootId()->toString()));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    private function firstStoredEventIn(string $eventClass, string $streamName): object
    {
        $stream = $this->service(Store::class)->load(new CriteriaBuilder()->streamName($streamName)->build());

        foreach ($stream as $message) {
            $event = $message->event();

            if ($event instanceof $eventClass) {
                return $event;
            }
        }

        self::fail(\sprintf('%s event not found in %s.', $eventClass, $streamName));
    }
}
