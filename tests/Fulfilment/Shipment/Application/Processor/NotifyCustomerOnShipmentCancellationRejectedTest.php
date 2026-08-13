<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotifierInterface;
use Fulfilment\Shipment\Application\Processor\NotifyCustomerOnShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class NotifyCustomerOnShipmentCancellationRejectedTest extends AbstractIntegrationTestCase
{
    private const string REJECTED_AT = '2026-01-02T00:00:00+00:00';

    private DummyShipmentCancellationRejectedNotifier $notifier;

    private NotifyCustomerOnShipmentCancellationRejected $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new DummyShipmentCancellationRejectedNotifier();
        $this->processor = new NotifyCustomerOnShipmentCancellationRejected(
            $this->service(ShipmentRepositoryInterface::class),
            $this->notifier,
        );
    }

    #[Test]
    public function itNotifiesTheCustomerOnShipmentCancellationRejected(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()
            ->withCustomerId($customerId)
            ->withCustomerAddress('buyer@example.com')
            ->dispatched()
            ->store();

        // When
        ($this->processor)(new ShipmentCancellationRejected($shipment->id()->toString(), ShipmentState::DISPATCHED->value, self::REJECTED_AT));

        // Then
        $notification = $this->notifier->notification;
        self::assertInstanceOf(ShipmentCancellationRejectedNotification::class, $notification);
        self::assertSame($shipment->id()->toString(), $notification->shipmentId);
        self::assertSame($shipment->orderId(), $notification->orderId);
        self::assertSame($customerId, $notification->customerId);
        self::assertSame('buyer@example.com', $notification->customerAddress);
    }
}

final class DummyShipmentCancellationRejectedNotifier implements ShipmentCancellationRejectedNotifierInterface
{
    public ?ShipmentCancellationRejectedNotification $notification = null;

    public function notify(ShipmentCancellationRejectedNotification $notification): void
    {
        $this->notification = $notification;
    }
}
