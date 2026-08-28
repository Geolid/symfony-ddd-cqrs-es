<?php

declare(strict_types=1);

namespace Shared\Infrastructure\EventStore\Publisher;

use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\Store;
use Psr\Clock\ClockInterface;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Webmozart\Assert\Assert;

/**
 * No PlayheadHeader: this repo's store is StreamDoctrineDbalStore (config 'dbal_stream'),
 * whose playhead column is nullable with a unique index on (stream, playhead) — standard SQL
 * never treats NULL as equal to NULL, so concurrent appends on the same stream never collide.
 */
final readonly class EventSourcingIntegrationEventPublisher implements IntegrationEventPublisherInterface
{
    public function __construct(
        private Store $store,
        private ClockInterface $clock,
    ) {
    }

    public function publish(string $aggregateClass, string $aggregateId, IntegrationEventInterface $event): void
    {
        Assert::subclassOf(
            $aggregateClass,
            AggregateRootMetadataAware::class,
            'The aggregate class "%s" must implement %2$s to resolve its stream name.',
        );

        $streamName = 'integration.'.$aggregateClass::metadata()->streamName($aggregateId);

        $this->store->save(
            Message::create($event)
                ->withHeader(new StreamNameHeader($streamName))
                ->withHeader(new RecordedOnHeader($this->clock->now())),
        );
    }
}
