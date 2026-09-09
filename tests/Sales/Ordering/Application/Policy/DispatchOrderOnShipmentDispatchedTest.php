<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\DispatchOrderOnShipmentDispatched;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DispatchOrderOnShipmentDispatchedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDispatches(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->create();
        $this->store($order);

        // When
        $this->trigger(DispatchOrderOnShipmentDispatched::class, new ShipmentDispatchedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
    }
}
