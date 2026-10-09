<?php

declare(strict_types=1);

namespace Support\TestCase;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
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
     * The persisted event of $eventClass, scanning the whole store.
     *
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
     * The matching event as the event store returns it: decrypted while its subject's cipher key exists,
     * replaced by its erasure fallback once the key is dropped.
     *
     * @template T of object
     *
     * @param class-string<T>   $eventClass
     * @param callable(T): bool $matches
     *
     * @return T
     */
    protected function storedEventOf(string $eventClass, callable $matches): object
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof $eventClass && $matches($event)) {
                return $event;
            }
        }

        self::fail(\sprintf('%s event not found in the stream.', $eventClass));
    }
}
