<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Translator;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Support\AbstractIntegrationTestCase;

final class OrderIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesThePlacementOnOrderPlaced(): void
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
        $published = $this->publishedTo(IntegrationStreamId::build('sales.order', $order->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(OrderPlacedIntegrationEvent::class, $event);
        self::assertSame($order->id()->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame(2_500, $event->totalAmountInCents);
    }

    #[Test]
    public function itPublishesTheCancellationOnOrderCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.order', $order->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(OrderCancelledIntegrationEvent::class, $event);
        self::assertSame($order->id()->toString(), $event->orderId);
    }

    #[Test]
    public function itPublishesTheRequestOnOrderPaymentRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.order', $orderId));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(OrderPaymentRequestedIntegrationEvent::class, $event);
        self::assertSame($orderId, $event->orderId);
        self::assertSame(2_500, $event->amountInCents);
        self::assertSame('GLBX-ABC12345', $event->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $event->checkoutUrl);
    }

    #[Test]
    public function itPublishesTheCaptureOnOrderPaymentCaptured(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->captured()
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.order', $order->id()->toString()));
        self::assertCount(3, $published);
        self::assertInstanceOf(OrderPaymentRequestedIntegrationEvent::class, $published[1]);
        $event = $published[2];
        self::assertInstanceOf(OrderPaymentCapturedIntegrationEvent::class, $event);
        self::assertSame($order->id()->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame($order->shippingAddress()->address->street, $event->shippingStreet);
    }
}
