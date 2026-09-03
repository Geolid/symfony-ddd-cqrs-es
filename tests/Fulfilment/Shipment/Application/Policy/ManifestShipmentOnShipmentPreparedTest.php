<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class ManifestShipmentOnShipmentPreparedTest extends AbstractIntegrationTestCase
{
    private SpyCarrierGateway $carrier;

    private ManifestShipmentOnShipmentPrepared $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = new SpyCarrierGateway();
        self::getContainer()->set(CarrierGatewayInterface::class, $this->carrier);

        $this->policy = $this->service(ManifestShipmentOnShipmentPrepared::class);
    }

    #[Test]
    public function itManifests(): void
    {
        // Given
        $shippingAddress = PostalAddress::of(
            FullName::of('Ada', 'Lovelace'),
            Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'),
        );
        $shipment = ShipmentBuilder::new()->withShippingAddress($shippingAddress)->prepared()->create();
        $this->store($shipment);

        // When
        ($this->policy)(new ShipmentPrepared($shipment->id->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        self::assertNotNull($this->carrier->deliveryAddress);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            [
                'firstName' => $this->carrier->deliveryAddress->fullName->firstName,
                'lastName' => $this->carrier->deliveryAddress->fullName->lastName,
                'street' => $this->carrier->deliveryAddress->address->street,
                'postalCode' => $this->carrier->deliveryAddress->address->postalCode,
                'city' => $this->carrier->deliveryAddress->address->city,
                'countryCode' => $this->carrier->deliveryAddress->address->countryCode->value,
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

    public function manifest(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $this->deliveryAddress = $deliveryAddress;

        return self::TRACKING_REFERENCE;
    }

    public function manifestReturn(string $shipmentId, PostalAddress $pickupAddress): string
    {
        throw new \LogicException('Not exercised by this test.');
    }

    public function checkStatus(string $reference): CarrierGatewayStatus
    {
        throw new \LogicException('Not exercised by this test.');
    }
}
