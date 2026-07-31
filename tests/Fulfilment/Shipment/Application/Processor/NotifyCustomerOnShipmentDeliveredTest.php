<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Fulfilment\Shipment\Application\Processor\NotifyCustomerOnShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class NotifyCustomerOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    private const string DELIVERED_AT = '2026-01-02T00:00:00+00:00';

    private DummyShipmentDeliveredNotifier $notifier;

    private NotifyCustomerOnShipmentDelivered $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new DummyShipmentDeliveredNotifier();
        $this->processor = new NotifyCustomerOnShipmentDelivered(
            $this->service(ShipmentRepositoryInterface::class),
            $this->notifier,
        );
    }

    #[Test]
    public function itNotifiesTheCustomerOnShipmentDelivered(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()
            ->withCustomerId('customer-1')
            ->withCustomerAddress('buyer@example.com')
            ->delivered()
            ->create();
        $this->store($shipment);

        // When
        ($this->processor)(new ShipmentDelivered($shipment->id()->toString(), self::DELIVERED_AT));

        // Then
        self::assertEquals(
            new ShipmentDeliveredNotification(
                $shipment->id()->toString(),
                $shipment->orderId(),
                'customer-1',
                'buyer@example.com',
            ),
            $this->notifier->notification,
        );
    }

    #[Test]
    public function itSkipsAShipmentWithoutAddressOnShipmentDelivered(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->withCustomerAddress(null)->delivered()->create();
        $this->store($shipment);

        // When
        ($this->processor)(new ShipmentDelivered($shipment->id()->toString(), self::DELIVERED_AT));

        // Then
        self::assertNull($this->notifier->notification);
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
