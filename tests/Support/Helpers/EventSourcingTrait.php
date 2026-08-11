<?php

declare(strict_types=1);

namespace Support\Helpers;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Serializer\SerializedEvent;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;

trait EventSourcingTrait
{
    abstract protected function service(string $serviceId): mixed;

    /**
     * Saves aggregates, synchronously triggering translators, projectors, and sync processors.
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
     * Retrieves all events published to a specific stream.
     *
     * @return list<object>
     */
    protected function publishedTo(string $streamId): array
    {
        $published = [];

        foreach ($this->service(Store::class)->load(new Criteria(new StreamCriterion($streamId))) as $message) {
            $published[] = $message->event();
        }

        return $published;
    }

    /**
     * Serializes the matching event now, while its subject's cipher key still exists — the InMemoryStore
     * used in tests never round-trips events through the serializer, so crypto-shredding can't be
     * observed on it otherwise.
     *
     * @template T of object
     *
     * @param class-string<T>   $eventClass
     * @param callable(T): bool $matches
     */
    protected function serializedEventOf(string $eventClass, callable $matches): SerializedEvent
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof $eventClass && $matches($event)) {
                return $this->service(EventSerializer::class)->serialize($event);
            }
        }

        self::fail(\sprintf('%s event not found in the stream.', $eventClass));
    }
}
