<?php

declare(strict_types=1);

namespace Support\Helpers;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Serializer\SerializedEvent;
use Patchlevel\EventSourcing\Store\Store;

trait EventSourcingTrait
{
    abstract protected function service(string $serviceId): mixed;

    /**
     * Saves aggregates, synchronously triggering publishers, projectors, and sync processors.
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
     * The persisted event of $eventClass, scanning the whole store.
     *
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    protected function publishedEventOfType(string $eventClass): object
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
