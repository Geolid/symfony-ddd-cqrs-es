<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderDispatched;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderDispatched\OrderDispatchedIntegrationEvent;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderDispatchedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderDispatchedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['dispatchedAt']->format(\DateTimeInterface::ATOM), $event->dispatchedAt->format(\DateTimeInterface::ATOM));
    }
}
