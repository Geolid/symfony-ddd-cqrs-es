<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Shared\Tests\Support\Doubles\StubDataSubjectErased;
use Support\AbstractIntegrationTestCase;

final class OrderPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsShippingAddressOnCustomerErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new StubDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->shippingAddress);
        self::assertNotSame('erased', $rehydrated->billingAddress['street']);
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnBillingRetentionExpiry(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new StubDataSubjectErased($order->id->toString())),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->billingAddress);
        self::assertNotSame('erased', $rehydrated->shippingAddress['street']);
    }

    #[Test]
    public function itCryptoShredsPaymentCapturedShippingAddressOnCustomerErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $this->store($order, $orderPayment);
        $serialized = $this->serializedEventOf(
            OrderPaymentCapturedIntegrationEvent::class,
            static fn (OrderPaymentCapturedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new StubDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPaymentCapturedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->shippingAddress);
    }

    #[Test]
    public function itCryptoShredsOrderConfirmedShippingAddressOnCustomerErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderConfirmedIntegrationEvent::class,
            static fn (OrderConfirmedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new StubDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderConfirmedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->shippingAddress);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function erasedAddress(): array
    {
        return ['firstName' => 'erased', 'lastName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased'];
    }
}
