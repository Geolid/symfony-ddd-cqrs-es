<?php

declare(strict_types=1);

namespace Support\TestCase;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\Store;

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
     * Replaced by its erasure fallback once its subject's cipher key is dropped.
     *
     * @template T of object
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    protected function storedEventOf(string $eventClass, string $aggregateId): object
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof $eventClass && str_ends_with($message->header(StreamNameHeader::class)->streamName, $aggregateId)) {
                return $event;
            }
        }

        self::fail(\sprintf('%s event not found for %s.', $eventClass, $aggregateId));
    }
}
