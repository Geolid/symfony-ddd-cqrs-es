<?php

declare(strict_types=1);

namespace Shared\Infrastructure\EventStore;

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
final readonly class PatchlevelIntegrationEventPublisher implements IntegrationEventPublisherInterface
{
    public function __construct(
        private Store $store,
        private ClockInterface $clock,
    ) {
    }

    public static function streamNameOf(string $aggregateClass, string $aggregateId): string
    {
        Assert::subclassOf(
            $aggregateClass,
            AggregateRootMetadataAware::class,
            'The aggregate class "%s" must implement %2$s to resolve its stream name.',
        );

        return 'integration.'.$aggregateClass::metadata()->streamName($aggregateId);
    }

    public function publish(string $aggregateClass, string $aggregateId, IntegrationEventInterface $event): void
    {
        $this->store->save(
            Message::create($event)
                ->withHeader(new StreamNameHeader(self::streamNameOf($aggregateClass, $aggregateId)))
                ->withHeader(new RecordedOnHeader($this->clock->now())),
        );
    }
}
