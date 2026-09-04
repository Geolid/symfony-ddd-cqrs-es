<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Double\StubDataSubjectErased;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPiiErasureTest extends AbstractIntegrationTestCase
{
    private DataSubjectEraserProcessor $eraser;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eraser = $this->service(DataSubjectEraserProcessor::class);
        $this->serializer = $this->service(EventSerializer::class);
    }

    #[Test]
    public function itCryptoShredsAddressesOnBuyerErasure(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()
            ->withBuyerId($buyerId)
            ->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new StubDataSubjectErased($buyerId)));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->shippingAddress);
        self::assertSame($this->erasedAddress(), $rehydrated->billingAddress);
    }

    #[Test]
    public function itCryptoShredsOrderPlacedBillingAddressOnBuyerErasure(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderPlacedIntegrationEvent::class,
            static fn (OrderPlacedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new StubDataSubjectErased($buyerId)));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(OrderPlacedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->billingAddress);
    }

    #[Test]
    public function itCryptoShredsOrderConfirmedShippingAddressOnBuyerErasure(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->confirmed()->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderConfirmedIntegrationEvent::class,
            static fn (OrderConfirmedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new StubDataSubjectErased($buyerId)));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(OrderConfirmedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->shippingAddress);
    }

    /**
     * @return array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function erasedAddress(): array
    {
        return ['recipientName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'];
    }
}
