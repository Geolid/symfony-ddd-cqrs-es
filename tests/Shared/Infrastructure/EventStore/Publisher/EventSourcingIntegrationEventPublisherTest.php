<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\EventStore\Publisher;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Id;
use Patchlevel\EventSourcing\Store\Header\PlayheadHeader;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\InMemoryStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Infrastructure\EventStore\Publisher\EventSourcingIntegrationEventPublisher;
use Symfony\Component\Clock\MockClock;

final class EventSourcingIntegrationEventPublisherTest extends TestCase
{
    private InMemoryStore $store;
    private EventSourcingIntegrationEventPublisher $publisher;

    protected function setUp(): void
    {
        $this->store = new InMemoryStore();
        $this->publisher = new EventSourcingIntegrationEventPublisher($this->store, new MockClock('2026-01-01T00:00:00+00:00'));
    }

    #[Test]
    public function itPublishes(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $event = new DummyIntegrationEvent();

        // When
        $this->publisher->publish(FakeAggregate::class, $id, $event);

        // Then
        $messages = [];
        foreach ($this->store->load() as $message) {
            $messages[] = $message;
        }
        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertSame($event, $message->event());
        self::assertSame('integration.fake_aggregate-'.$id, $message->header(StreamNameHeader::class)->streamName);
        self::assertSame('2026-01-01T00:00:00+00:00', $message->header(RecordedOnHeader::class)->recordedOn->format(\DATE_ATOM));
        self::assertFalse($message->hasHeader(PlayheadHeader::class));
    }

    #[Test]
    public function itThrowsWhenNotStreamAware(): void
    {
        // Then
        self::expectException(\InvalidArgumentException::class);

        // When
        $this->publisher->publish(\stdClass::class, Uuid::uuid7()->toString(), new DummyIntegrationEvent());
    }
}

final class DummyIntegrationEvent implements IntegrationEventInterface
{
}

#[Aggregate('fake_aggregate')]
final class FakeAggregate implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private string $id = '';
}
