<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Publisher;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderConfirmedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Application\Event\OrderReturnRequestedIntegrationEvent;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class OrderPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesOnOrderPlaced(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withTotalAmountInCents(2_500)
            ->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderPlacedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame(2_500, $event->totalAmountInCents);
    }

    #[Test]
    public function itPublishesOnOrderCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderCancelledIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
    }

    #[Test]
    public function itPublishesOnOrderConfirmed(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderConfirmedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame($this->address($order->shippingAddress), $event->shippingAddress);
    }

    #[Test]
    public function itPublishesOnOrderPaymentRequested(): void
    {
        // Given
        $orderId = OrderId::generate()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOfType(OrderPaymentRequestedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSame(2_500, $event->amountInCents);
        self::assertSame('GLBX-ABC12345', $event->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $event->checkoutUrl);
    }

    #[Test]
    public function itPublishesOnOrderPaymentCaptured(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->authorized()
            ->captured()
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOfType(OrderPaymentCapturedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame($this->address($order->shippingAddress), $event->shippingAddress);
    }

    #[Test]
    public function itPublishesOnOrderReturnRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderReturnRequestedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function address(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
        ];
    }
}
