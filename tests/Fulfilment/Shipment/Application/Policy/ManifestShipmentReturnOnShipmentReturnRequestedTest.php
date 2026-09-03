<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\ManifestShipmentReturn\ManifestShipmentReturn;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentReturnOnShipmentReturnRequested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ManifestShipmentReturnOnShipmentReturnRequestedTest extends AbstractIntegrationTestCase
{
    private CarrierGatewayInterface&MockObject $carrier;

    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = $this->createMock(CarrierGatewayInterface::class);
        $this->replace(CarrierGatewayInterface::class, $this->carrier);

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $this->commandBus);
    }

    #[Test]
    public function itManifests(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->create();
        $this->store($shipment);

        $pickupAddress = null;
        $returnTrackingReference = ShipmentBuilder::sample('returnTrackingReference')->value;
        $this->carrier->expects(self::once())->method('manifestReturn')
            ->willReturnCallback(static function (string $shipmentId, PostalAddress $address) use (&$pickupAddress, $returnTrackingReference): string {
                $pickupAddress = $address;

                return $returnTrackingReference;
            });

        $dispatched = null;
        $this->commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(ManifestShipmentReturnOnShipmentReturnRequested::class, new ShipmentReturnRequested($shipment->id->toString(), Clock::get()->now()));

        // Then
        self::assertNotNull($pickupAddress);
        self::assertSame($this->rawAddress($shipment->shippingAddress), $this->rawAddress($pickupAddress));

        self::assertInstanceOf(ManifestShipmentReturn::class, $dispatched);
        self::assertSame($shipment->id->toString(), $dispatched->id);
        self::assertSame($returnTrackingReference, $dispatched->returnTrackingReference);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function rawAddress(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
