<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\ManifestShipmentReturnOnShipmentReturnRequested;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class ManifestShipmentReturnOnShipmentReturnRequestedTest extends AbstractIntegrationTestCase
{
    private SpyReturnCarrierGateway $carrier;

    private ManifestShipmentReturnOnShipmentReturnRequested $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = new SpyReturnCarrierGateway();
        self::getContainer()->set(CarrierGatewayInterface::class, $this->carrier);

        $this->processor = $this->service(ManifestShipmentReturnOnShipmentReturnRequested::class);
    }

    #[Test]
    public function itManifestsOnShipmentReturnRequested(): void
    {
        // Given
        $shippingAddress = PostalAddress::of(
            FullName::of('Ada', 'Lovelace'),
            Address::of('12 rue des Lilas', '75001', 'Paris'),
        );
        $shipment = ShipmentTestFactory::new()->withShippingAddress($shippingAddress)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->store();

        // When
        ($this->processor)(new ShipmentReturnRequested($shipment->id->toString(), '2026-01-10T00:00:00+00:00'));

        // Then
        self::assertNotNull($this->carrier->pickupAddress);
        self::assertSame('12 rue des Lilas', $this->carrier->pickupAddress->address->street);
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $results[0]->status);
        self::assertSame(SpyReturnCarrierGateway::RETURN_TRACKING_REFERENCE, $results[0]->returnTrackingReference);
    }
}

final class SpyReturnCarrierGateway implements CarrierGatewayInterface
{
    public const string RETURN_TRACKING_REFERENCE = 'ACME-RETURN-4Q7X2K9';

    public ?PostalAddress $pickupAddress = null;

    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        throw new \LogicException('Not exercised by this test.');
    }

    public function requestReturnPickup(string $shipmentId, PostalAddress $pickupAddress): string
    {
        $this->pickupAddress = $pickupAddress;

        return self::RETURN_TRACKING_REFERENCE;
    }
}
