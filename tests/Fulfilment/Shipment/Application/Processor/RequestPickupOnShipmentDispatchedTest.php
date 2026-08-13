<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\RequestPickupOnShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
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
            ->store();

        // When
        ($this->processor)(new ShipmentDispatched($shipment->id()->toString(), self::DISPATCHED_AT));

        // Then
        self::assertSame('buyer@example.com', $this->carrier->deliveryAddress);
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(DummyCarrierGateway::TRACKING_REFERENCE, $results[0]->trackingReference);
    }

    #[Test]
    public function itSkipsRequestingPickupOnShipmentDispatchedWhenAlreadyCancelled(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->cancelled()->store();

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
