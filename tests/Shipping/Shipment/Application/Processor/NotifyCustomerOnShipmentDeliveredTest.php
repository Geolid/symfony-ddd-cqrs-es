<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Application\Processor;

use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shipping\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Shipping\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Shipping\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Shipping\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Shipping\Shipment\Application\Processor\NotifyCustomerOnShipmentDelivered;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer;
use Support\AbstractIntegrationTestCase;

final class NotifyCustomerOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itNotifiesTheCustomerOnceTheShipmentIsDelivered(): void
    {
        // Given — an order all the way to a delivered shipment.
        $orderId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 1_500));
        ($this->service(CreateShipmentOnOrderPlaced::class))(new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            totalAmountInCents: 1_500,
            placedAt: (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->format('c'),
        ));

        $shipmentId = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)))[0]->id;

        $this->dispatch(new DispatchShipment($shipmentId));
        $this->dispatch(new MarkShipmentDelivered($shipmentId));

        $notifier = new class implements ShipmentDeliveredNotifierInterface {
            public ?string $notifiedShipmentId = null;
            public ?string $notifiedOrderId = null;
            public ?string $notifiedCustomerId = null;

            public function notify(string $shipmentId, string $orderId, string $customerId): void
            {
                $this->notifiedShipmentId = $shipmentId;
                $this->notifiedOrderId = $orderId;
                $this->notifiedCustomerId = $customerId;
            }
        };

        $processor = new NotifyCustomerOnShipmentDelivered(
            $this->service(ShipmentRepositoryInterface::class),
            $this->service(OrderSummaryReducer::class),
            $notifier,
        );

        // When
        $processor(new ShipmentDelivered($shipmentId, (new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))->format('c')));

        // Then
        self::assertSame($shipmentId, $notifier->notifiedShipmentId);
        self::assertSame($orderId, $notifier->notifiedOrderId);
        self::assertSame('customer-1', $notifier->notifiedCustomerId);
    }
}
