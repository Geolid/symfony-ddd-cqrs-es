<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\DeliverOrderOnShipmentDelivered;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;

final class DeliverOrderOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    private DeliverOrderOnShipmentDelivered $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(DeliverOrderOnShipmentDelivered::class);
    }

    #[Test]
    public function itDelivers(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->create();
        $this->store($order);

        // When
        ($this->policy)(new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DELIVERED, $result->status);
    }
}
