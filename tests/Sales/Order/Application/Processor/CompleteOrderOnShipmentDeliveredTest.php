<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\CompleteOrderOnShipmentDelivered;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CompleteOrderOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    private const string DELIVERED_AT = '2026-01-02T00:00:00+00:00';

    private CompleteOrderOnShipmentDelivered $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CompleteOrderOnShipmentDelivered::class);
    }

    #[Test]
    public function itCompletesTheOrderOnShipmentDelivered(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->store();

        // When
        ($this->processor)(new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $order->id()->toString(), self::DELIVERED_AT));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::COMPLETED, $result->status);
    }
}
