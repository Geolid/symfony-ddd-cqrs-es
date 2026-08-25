<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPlaced;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderPlacedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withTotalAmountInCents(2_500)
            ->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderPlacedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame(2_500, $event->totalAmountInCents);
    }
}
