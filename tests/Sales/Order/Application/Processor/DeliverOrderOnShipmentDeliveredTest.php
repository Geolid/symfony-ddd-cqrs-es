<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\DeliverOrderOnShipmentDelivered;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DeliverOrderOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    private DeliverOrderOnShipmentDelivered $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(DeliverOrderOnShipmentDelivered::class);
    }

    #[Test]
    public function itDeliversOnShipmentDelivered(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->store();

        // When
        ($this->processor)(new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $order->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::DELIVERED, $result->status);
    }
}
