<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentReturnApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\StartOrderRefundOnShipmentReturnApproved;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class StartOrderRefundOnShipmentReturnApprovedTest extends AbstractIntegrationTestCase
{
    private StartOrderRefundOnShipmentReturnApproved $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(StartOrderRefundOnShipmentReturnApproved::class);
    }

    #[Test]
    public function itStartsTheOrderRefundOnShipmentReturnApproved(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->returnRequested()->store();

        // When
        ($this->processor)(new ShipmentReturnApprovedIntegrationEvent(Uuid::uuid7()->toString(), $order->id()->toString(), '2026-01-11T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::REFUNDING, $result->status);
    }
}
