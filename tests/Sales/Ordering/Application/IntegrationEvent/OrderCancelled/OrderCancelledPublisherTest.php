<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderCancelled;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new()->cancelled();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderCancelledIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['cancelledAt']->format(\DateTimeInterface::ATOM), $event->cancelledAt->format(\DateTimeInterface::ATOM));
    }
}
