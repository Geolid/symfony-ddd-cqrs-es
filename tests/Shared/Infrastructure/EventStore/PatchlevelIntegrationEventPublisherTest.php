<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Id;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Header\EventIdHeader;
use Patchlevel\EventSourcing\Store\Header\PlayheadHeader;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\InMemoryStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Infrastructure\EventStore\PatchlevelIntegrationEventPublisher;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class PatchlevelIntegrationEventPublisherTest extends TestCase
{
    use AssertionTrait;

    private InMemoryStore $store;
    private PatchlevelIntegrationEventPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new InMemoryStore();
        $this->publisher = new PatchlevelIntegrationEventPublisher($this->store, Clock::get());
    }

    #[Test]
    public function itPublishes(): void
    {
        // Given
        $now = Clock::get()->now();
        $event = new DummyIntegrationEvent();
        $source = Message::create(new \stdClass())->withHeader(new EventIdHeader('source-event-id'));

        // When
        $this->publisher->publish(FakeAggregate::class, 'aggregate-id', $event, $source);

        // Then
        $messages = iterator_to_array($this->store->load());
        self::assertCount(1, $messages);

        $message = $messages[0];
        self::assertSame($event, $message->event());
        self::assertSame('integration.fake_aggregate-aggregate-id', $message->header(StreamNameHeader::class)->streamName);
        self::assertFalse($message->hasHeader(PlayheadHeader::class));

        self::assertSameDateTime($now, $message->header(RecordedOnHeader::class)->recordedOn);
    }

    #[Test]
    public function itPublishesWithEventIdDerivedFromSource(): void
    {
        // Given
        $source = Message::create(new \stdClass())->withHeader(new EventIdHeader('source-event-id'));
        $otherStore = new InMemoryStore();
        $otherPublisher = new PatchlevelIntegrationEventPublisher($otherStore, Clock::get());

        // When
        $this->publisher->publish(FakeAggregate::class, 'aggregate-id', new DummyIntegrationEvent(), $source);

        // Then
        $otherPublisher->publish(FakeAggregate::class, 'aggregate-id', new DummyIntegrationEvent(), $source);
        $eventId = iterator_to_array($this->store->load())[0]->header(EventIdHeader::class)->eventId;
        $otherEventId = iterator_to_array($otherStore->load())[0]->header(EventIdHeader::class)->eventId;
        self::assertSame($eventId, $otherEventId);
    }

    #[Test]
    public function itPublishesWithDistinctEventIdWhenSourceDiffers(): void
    {
        // Given
        $source = Message::create(new \stdClass())->withHeader(new EventIdHeader('source-event-id'));
        $otherSource = Message::create(new \stdClass())->withHeader(new EventIdHeader('other-source-event-id'));
        $this->publisher->publish(FakeAggregate::class, 'aggregate-id', new DummyIntegrationEvent(), $source);

        // When
        $this->publisher->publish(FakeAggregate::class, 'aggregate-id', new DummyIntegrationEvent(), $otherSource);

        // Then
        $messages = iterator_to_array($this->store->load());
        self::assertNotSame($messages[0]->header(EventIdHeader::class)->eventId, $messages[1]->header(EventIdHeader::class)->eventId);
    }

    #[Test]
    public function itThrowsWhenNotStreamAware(): void
    {
        // Given
        $source = Message::create(new \stdClass())->withHeader(new EventIdHeader('source-event-id'));

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->publisher->publish(\stdClass::class, 'aggregate-id', new DummyIntegrationEvent(), $source);
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
