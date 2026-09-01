<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderCancelled;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class OrderCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $now = Clock::get()->now();
        $order = OrderBuilder::new()->cancelled($now)->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderCancelledIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->cancelledAt->format(\DateTimeImmutable::ATOM));
    }
}
