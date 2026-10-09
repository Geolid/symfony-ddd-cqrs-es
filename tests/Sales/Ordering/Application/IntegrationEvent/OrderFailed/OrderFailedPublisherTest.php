<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderFailed;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderFailed\OrderFailedIntegrationEvent;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderFailedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $order = OrderFactory::new()->failed()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderFailedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($order->customerId, $event->customerId);
        self::assertSame($order->failedAt?->format(\DateTimeInterface::ATOM), $event->failedAt->format(\DateTimeInterface::ATOM));
    }
}
