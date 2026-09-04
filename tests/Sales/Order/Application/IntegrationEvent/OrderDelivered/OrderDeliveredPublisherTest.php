<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderDelivered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderDelivered\OrderDeliveredIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderDeliveredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderDeliveredIntegrationEvent::class);
        $shippingAddress = $order->shippingAddress->toArray();
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['buyerId'], $event->buyerId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($builder['deliveredAt']->format(\DateTimeInterface::ATOM), $event->deliveredAt->format(\DateTimeInterface::ATOM));
    }
}
