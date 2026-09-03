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
use Symfony\Component\Clock\Clock;

final class RejectOrderReturnOnShipmentReturnRejectedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRejects(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);
        $reason = OrderBuilder::sample('returnRejectionReason');

        // When
        $this->trigger(RejectOrderReturnOnShipmentReturnRejected::class, new ShipmentReturnRejectedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            $reason,
            Clock::get()->now(),
        ));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURN_REJECTED, $result->status);
        self::assertSame($reason, $result->returnRejectionReason);
    }
}
