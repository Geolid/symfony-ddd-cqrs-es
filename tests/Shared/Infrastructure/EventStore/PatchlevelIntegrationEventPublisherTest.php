<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Header\EventIdHeader;
use Patchlevel\EventSourcing\Store\Header\PlayheadHeader;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\InMemoryStore;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Store\UniqueConstraintViolation;
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
        $source = Message::create(new \stdClass())
            ->withHeader(new StreamNameHeader('fake_aggregate-aggregate-id'))
            ->withHeader(new EventIdHeader('source-event-id'));

        // When
        $this->publisher->publish($event, $source);

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
    public function itIgnoresWhenAlreadyPublished(): void
    {
        // Given
        $store = $this->createStub(Store::class);
        $store->method('save')->willThrowException(new UniqueConstraintViolation());
        $publisher = new PatchlevelIntegrationEventPublisher($store, Clock::get());
        $source = Message::create(new \stdClass())
            ->withHeader(new StreamNameHeader('fake_aggregate-aggregate-id'))
            ->withHeader(new EventIdHeader('source-event-id'));

        // When
        $publisher->publish(new DummyIntegrationEvent(), $source);

        // Then
        self::expectNotToPerformAssertions();
    }
}

final class DummyIntegrationEvent implements IntegrationEventInterface
{
}
