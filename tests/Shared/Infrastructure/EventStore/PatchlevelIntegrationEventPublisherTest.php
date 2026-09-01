<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\EventStore;

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
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Infrastructure\EventStore\PatchlevelIntegrationEventPublisher;
use Symfony\Component\Clock\Clock;

final class PatchlevelIntegrationEventPublisherTest extends TestCase
{
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

        // When
        $this->publisher->publish(FakeAggregate::class, 'aggregate-id', $event);

        // Then
        $messages = iterator_to_array($this->store->load());
        self::assertCount(1, $messages);

        $message = $messages[0];
        self::assertSame($event, $message->event());
        self::assertSame('integration.fake_aggregate-aggregate-id', $message->header(StreamNameHeader::class)->streamName);
        self::assertSame($now->format(\DATE_ATOM), $message->header(RecordedOnHeader::class)->recordedOn->format(\DATE_ATOM));
        self::assertFalse($message->hasHeader(PlayheadHeader::class));
    }

    #[Test]
    public function itThrowsWhenNotStreamAware(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->publisher->publish(\stdClass::class, 'aggregate-id', new DummyIntegrationEvent());
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
