<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderCancelled;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderCancelledIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
    }
}
