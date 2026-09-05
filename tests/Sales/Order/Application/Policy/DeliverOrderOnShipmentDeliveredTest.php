<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\DeliverOrderOnShipmentDelivered;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DeliverOrderOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelivers(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->create();
        $this->store($order);

        // When
        $this->trigger(DeliverOrderOnShipmentDelivered::class, new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DELIVERED, $result->status);
    }
}
