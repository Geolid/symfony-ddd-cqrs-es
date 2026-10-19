<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderPaid;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderPaid\OrderPaidIntegrationEvent;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPaidPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->paid()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderPaidIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSameDateTime($order->paidAt, $event->paidAt);
    }
}
