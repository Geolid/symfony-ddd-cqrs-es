<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentReturnRejectedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\RejectOrderReturnOnShipmentReturnRejected;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RejectOrderReturnOnShipmentReturnRejectedTest extends AbstractIntegrationTestCase
{
    private RejectOrderReturnOnShipmentReturnRejected $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(RejectOrderReturnOnShipmentReturnRejected::class);
    }

    #[Test]
    public function itRejectsTheOrderReturnOnShipmentReturnRejected(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->returnRequested()->store();

        // When
        ($this->processor)(new ShipmentReturnRejectedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id()->toString(),
            'item damaged beyond resale',
            '2026-01-11T00:00:00+00:00',
        ));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::RETURN_REJECTED, $result->status);
        self::assertSame('item damaged beyond resale', $result->returnRejectionReason);
    }
}
