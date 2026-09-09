<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderConfirmed;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderConfirmedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderConfirmedIntegrationEvent::class);
        $shippingAddress = $order->shippingAddress->toArray();
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['buyerId'], $event->buyerId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($builder['confirmedAt']->format(\DateTimeInterface::ATOM), $event->confirmedAt->format(\DateTimeInterface::ATOM));
    }
}
