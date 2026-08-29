<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderReturnRequested;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderReturnRequested\OrderReturnRequestedIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class OrderReturnRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $now = Clock::get()->now();
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested($now)->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderReturnRequestedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->requestedAt->format(\DateTimeImmutable::ATOM));
    }
}
