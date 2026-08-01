<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Gateway\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Processor\RequestPickupOnShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class RequestPickupOnShipmentDispatchedTest extends AbstractIntegrationTestCase
{
    private const string DISPATCHED_AT = '2026-01-02T00:00:00+00:00';

    private DummyCarrierGateway $carrier;

    private RequestPickupOnShipmentDispatched $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = new DummyCarrierGateway();
        $this->processor = new RequestPickupOnShipmentDispatched(
            $this->service(ShipmentRepositoryInterface::class),
            $this->carrier,
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itTracksTheShipmentOnShipmentDispatched(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()
            ->withCustomerAddress('buyer@example.com')
            ->dispatched()
            ->create();
        $this->store($shipment);

        // When
        ($this->processor)(new ShipmentDispatched($shipment->id()->toString(), self::DISPATCHED_AT));

        // Then
        self::assertSame('buyer@example.com', $this->carrier->deliveryAddress);
        self::assertSame(
            DummyCarrierGateway::TRACKING_REFERENCE,
            $this->service(ShipmentRepositoryInterface::class)
                ->load(ShipmentId::fromString($shipment->id()->toString()))
                ->trackingReference(),
        );
    }

    #[Test]
    public function itSkipsAShipmentWithoutAddressOnShipmentDispatched(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->withCustomerAddress(null)->dispatched()->create();
        $this->store($shipment);

        // When
        ($this->processor)(new ShipmentDispatched($shipment->id()->toString(), self::DISPATCHED_AT));

        // Then
        self::assertNull($this->carrier->deliveryAddress);
    }
}

final class DummyCarrierGateway implements CarrierGatewayInterface
{
    public const string TRACKING_REFERENCE = 'ACME-4Q7X2K9';

    public ?string $deliveryAddress = null;

    public function requestPickup(string $shipmentId, string $deliveryAddress): string
    {
        $this->deliveryAddress = $deliveryAddress;

        return self::TRACKING_REFERENCE;
    }
}
