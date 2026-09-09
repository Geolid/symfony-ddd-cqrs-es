<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\DeliverOrderOnShipmentDelivered;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DeliverOrderOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelivers(): void
    {
        // Given
        $order = OrderBuilder::new()->prepared()->dispatched()->create();
        $this->store($order);

        // When
        $this->trigger(DeliverOrderOnShipmentDelivered::class, new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DELIVERED, $result->status);
    }
}
