<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesThePlacementOnOrderPlaced(): void
    {
        // When
        $order = OrderTestFactory::new()
            ->withCustomerId('customer-1')
            ->withBuyerAddress('buyer@example.com')
            ->withTotalAmountInCents(2_500)
            ->create();
        $this->store($order);

        // Then
        $published = $this->publishedTo(\sprintf('sales.order.integration.%s', $order->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(OrderPlacedIntegrationEvent::class, $event);
        self::assertSame($order->id()->toString(), $event->orderId);
        self::assertSame('customer-1', $event->customerId);
        self::assertSame('buyer@example.com', $event->buyerAddress);
        self::assertSame(2_500, $event->totalAmountInCents);
    }

    #[Test]
    public function itPublishesTheCancellationOnOrderCancelled(): void
    {
        // When
        $order = OrderTestFactory::new()->cancelled()->create();
        $this->store($order);

        // Then
        $published = $this->publishedTo(\sprintf('sales.order.integration.%s', $order->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(OrderCancelledIntegrationEvent::class, $event);
        self::assertSame($order->id()->toString(), $event->orderId);
    }

    #[Test]
    public function itPublishesTheRequestOnOrderPaymentRequested(): void
    {
        // When
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId('order-1')
            ->create();
        $this->store($orderPayment);

        // Then
        $published = $this->publishedTo('sales.order.integration.order-1');
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(OrderPaymentRequestedIntegrationEvent::class, $event);
        self::assertSame('order-1', $event->orderId);
    }

    #[Test]
    public function itPublishesTheCaptureOnOrderPaymentCaptured(): void
    {
        // When
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId('order-1')
            ->withCustomerId('customer-1')
            ->withBuyerAddress('buyer@example.com')
            ->captured()
            ->create();
        $this->store($orderPayment);

        // Then
        $published = $this->publishedTo('sales.order.integration.order-1');
        self::assertCount(2, $published);
        self::assertInstanceOf(OrderPaymentRequestedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(OrderPaymentCapturedIntegrationEvent::class, $event);
        self::assertSame('order-1', $event->orderId);
        self::assertSame('customer-1', $event->customerId);
        self::assertSame('buyer@example.com', $event->buyerAddress);
    }

    /**
     * @return list<object>
     */
    private function publishedTo(string $streamId): array
    {
        $published = [];

        foreach ($this->service(Store::class)->load(new Criteria(new StreamCriterion($streamId))) as $message) {
            $published[] = $message->event();
        }

        return $published;
    }
}
