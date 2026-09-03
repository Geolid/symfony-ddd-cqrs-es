<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnRejected\ShipmentReturnRejectedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\RejectOrderReturnOnShipmentReturnRejected;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RejectOrderReturnOnShipmentReturnRejectedTest extends AbstractIntegrationTestCase
{
    private RejectOrderReturnOnShipmentReturnRejected $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(RejectOrderReturnOnShipmentReturnRejected::class);
    }

    #[Test]
    public function itRejects(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        ($this->policy)(new ShipmentReturnRejectedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            'item damaged beyond resale',
            new \DateTimeImmutable('2026-01-11T00:00:00+00:00'),
        ));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURN_REJECTED, $result->status);
        self::assertSame('item damaged beyond resale', $result->returnRejectionReason);
    }
}
