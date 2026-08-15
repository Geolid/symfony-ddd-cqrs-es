<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\DispatchOrderOnShipmentDispatched;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DispatchOrderOnShipmentDispatchedTest extends AbstractIntegrationTestCase
{
    private DispatchOrderOnShipmentDispatched $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(DispatchOrderOnShipmentDispatched::class);
    }

    #[Test]
    public function itDispatchesTheOrderOnShipmentDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->store();

        // When
        ($this->processor)(new ShipmentDispatchedIntegrationEvent(Uuid::uuid7()->toString(), $order->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
    }
}
