<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Fulfilment\Shipment\Application\Processor\NotifyCustomerOnShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Domain\CustomerId;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\OrderId;
use Support\AbstractIntegrationTestCase;

final class NotifyCustomerOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    private const string DELIVERED_AT = '2026-01-02T00:00:00+00:00';

    private DummyShipmentDeliveredNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new DummyShipmentDeliveredNotifier();
    }

    #[Test]
    public function itNotifiesTheCustomerOnShipmentDelivered(): void
    {
        // Given
        $customerId = $this->registerCustomer('buyer@example.com');
        $orderId = $this->deliverShipmentFor($customerId);
        $shipmentId = ShipmentId::forOrder($orderId)->toString();

        // When
        ($this->processor())(new ShipmentDelivered($shipmentId, self::DELIVERED_AT));

        // Then
        self::assertEquals(
            new ShipmentDeliveredNotification($shipmentId, $orderId, $customerId, 'buyer@example.com'),
            $this->notifier->notification,
        );
    }

    #[Test]
    public function itSkipsAnErasedCustomerOnShipmentDelivered(): void
    {
        // Given
        $customerId = $this->registerCustomer('buyer@example.com');
        $orderId = $this->deliverShipmentFor($customerId);
        $this->dispatch(new EraseCustomer($customerId));

        // When
        ($this->processor())(new ShipmentDelivered(ShipmentId::forOrder($orderId)->toString(), self::DELIVERED_AT));

        // Then
        self::assertNull($this->notifier->notification);
    }

    private function processor(): NotifyCustomerOnShipmentDelivered
    {
        return new NotifyCustomerOnShipmentDelivered(
            $this->service(ShipmentRepositoryInterface::class),
            $this->notifier,
        );
    }

    private function registerCustomer(string $email): string
    {
        $id = CustomerId::generate()->toString();

        $this->dispatch(new RegisterCustomer($id, $email));

        return $id;
    }

    private function deliverShipmentFor(string $customerId): string
    {
        $orderId = OrderId::generate()->toString();

        $this->dispatch(new PlaceOrder($orderId, $customerId, 1_500));
        ($this->service(CreateShipmentOnOrderPlaced::class))(new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: $customerId,
            buyerAddress: 'buyer@example.com',
            totalAmountInCents: 1_500,
            placedAt: '2026-01-01T00:00:00+00:00',
        ));

        $shipmentId = ShipmentId::forOrder($orderId)->toString();

        $this->dispatch(new DispatchShipment($shipmentId));
        $this->dispatch(new MarkShipmentDelivered($shipmentId));

        return $orderId;
    }
}

final class DummyShipmentDeliveredNotifier implements ShipmentDeliveredNotifierInterface
{
    public ?ShipmentDeliveredNotification $notification = null;

    public function notify(ShipmentDeliveredNotification $notification): void
    {
        $this->notification = $notification;
    }
}
