<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderPrepared;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderPrepared\OrderPreparedIntegrationEvent;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPreparedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderPreparedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSameDateTime($order->preparedAt, $event->preparedAt);
    }
}
