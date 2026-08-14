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
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
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
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $shipment = ShipmentTestFactory::new()
            ->withShippingAddress($shippingAddress)
            ->dispatched()
            ->store();

        // When
        ($this->processor)(new ShipmentDispatched($shipment->id()->toString(), self::DISPATCHED_AT));

        // Then
        self::assertNotNull($this->carrier->deliveryAddress);
        self::assertTrue($shippingAddress->equals($this->carrier->deliveryAddress));
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(DummyCarrierGateway::TRACKING_REFERENCE, $results[0]->trackingReference);
    }
}

final class DummyCarrierGateway implements CarrierGatewayInterface
{
    public const string TRACKING_REFERENCE = 'ACME-4Q7X2K9';

    public ?PostalAddress $deliveryAddress = null;

    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $this->deliveryAddress = $deliveryAddress;

        return self::TRACKING_REFERENCE;
    }
}
