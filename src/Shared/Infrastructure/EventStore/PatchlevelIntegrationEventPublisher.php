<?php

declare(strict_types=1);

namespace Shared\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Header\EventIdHeader;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Store\UniqueConstraintViolation;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;

/**
 * No PlayheadHeader: this repo's store is StreamDoctrineDbalStore (config 'dbal_stream'),
 * whose playhead column is nullable with a unique index on (stream, playhead) — standard SQL
 * never treats NULL as equal to NULL, so concurrent appends on the same stream never collide.
 */
final readonly class PatchlevelIntegrationEventPublisher implements IntegrationEventPublisherInterface
{
    private const string EVENT_ID_NAMESPACE = '6b0c1f0e-3a55-4d8e-9f5c-2c7e1a4b9d10';

    public function __construct(
        private Store $store,
        private ClockInterface $clock,
    ) {
    }

    public function publish(IntegrationEventInterface $event, Message $source): void
    {
        try {
            $this->store->save(
                Message::create($event)
                    ->withHeader(new StreamNameHeader('integration.'.$source->header(StreamNameHeader::class)->streamName))
                    ->withHeader(new RecordedOnHeader($this->clock->now()))
                    ->withHeader(new EventIdHeader($this->eventIdOf($source, $event))),
            );
        } catch (UniqueConstraintViolation) {
        }
    }

    private function eventIdOf(Message $source, IntegrationEventInterface $event): string
    {
        return Uuid::uuid5(self::EVENT_ID_NAMESPACE, \sprintf('%s|%s', $source->header(EventIdHeader::class)->eventId, $event::class))->toString();
    }
}
