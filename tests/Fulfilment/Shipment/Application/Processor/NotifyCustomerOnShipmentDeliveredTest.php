<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Fulfilment\Shipment\Application\Processor\NotifyCustomerOnShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class NotifyCustomerOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itNotifiesTheCustomerOnceTheShipmentIsDelivered(): void
    {
        // Given
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

        $notifier = new DummyShipmentDeliveredNotifier();

        $processor = new NotifyCustomerOnShipmentDelivered(
            $this->service(ShipmentRepositoryInterface::class),
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

final class DummyShipmentDeliveredNotifier implements ShipmentDeliveredNotifierInterface
{
    public ?string $notifiedShipmentId = null;
    public ?string $notifiedOrderId = null;
    public ?string $notifiedCustomerId = null;

    public function notify(string $shipmentId, string $orderId, string $customerId): void
    {
        $this->notifiedShipmentId = $shipmentId;
        $this->notifiedOrderId = $orderId;
        $this->notifiedCustomerId = $customerId;
    }
}
