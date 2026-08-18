<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class ManifestShipmentOnShipmentPreparedTest extends AbstractIntegrationTestCase
{
    private SpyCarrierGateway $carrier;

    private ManifestShipmentOnShipmentPrepared $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = new SpyCarrierGateway();
        self::getContainer()->set(CarrierGatewayInterface::class, $this->carrier);

        $this->processor = $this->service(ManifestShipmentOnShipmentPrepared::class);
    }

    #[Test]
    public function itManifestsThePreparedShipmentOnShipmentPrepared(): void
    {
        // Given
        $shippingAddress = PostalAddress::of(
            FullName::of('Ada', 'Lovelace'),
            Address::of('12 rue des Lilas', '75001', 'Paris'),
        );
        $shipment = ShipmentTestFactory::new()->withShippingAddress($shippingAddress)->prepared()->store();

        // When
        ($this->processor)(new ShipmentPrepared($shipment->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertNotNull($this->carrier->deliveryAddress);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            [
                'firstName' => $this->carrier->deliveryAddress->fullName->firstName,
                'lastName' => $this->carrier->deliveryAddress->fullName->lastName,
                'street' => $this->carrier->deliveryAddress->address->street,
                'postalCode' => $this->carrier->deliveryAddress->address->postalCode,
                'city' => $this->carrier->deliveryAddress->address->city,
            ],
        );
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::MANIFESTED, $results[0]->status);
        self::assertSame(SpyCarrierGateway::TRACKING_REFERENCE, $results[0]->trackingReference);
    }
}

final class SpyCarrierGateway implements CarrierGatewayInterface
{
    public const string TRACKING_REFERENCE = 'ACME-4Q7X2K9';

    public ?PostalAddress $deliveryAddress = null;

    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $this->deliveryAddress = $deliveryAddress;

        return self::TRACKING_REFERENCE;
    }

    public function requestReturnPickup(string $shipmentId, PostalAddress $pickupAddress): string
    {
        throw new \LogicException('Not exercised by this test.');
    }
}
