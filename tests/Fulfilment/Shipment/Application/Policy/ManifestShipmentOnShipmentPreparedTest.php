<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ManifestShipmentOnShipmentPreparedTest extends AbstractIntegrationTestCase
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
        $shipment = ShipmentBuilder::new()->prepared()->create();
        $this->store($shipment);

        $deliveryAddress = null;
        $trackingReference = ShipmentBuilder::sample('trackingReference')->value;
        $this->carrier->expects(self::once())->method('manifest')
            ->willReturnCallback(static function (string $shipmentId, PostalAddress $address) use (&$deliveryAddress, $trackingReference): string {
                $deliveryAddress = $address;

                return $trackingReference;
            });

        $dispatched = null;
        $this->commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(ManifestShipmentOnShipmentPrepared::class, new ShipmentPrepared($shipment->id->toString(), Clock::get()->now()));

        // Then
        self::assertNotNull($deliveryAddress);
        self::assertSame($this->rawAddress($shipment->shippingAddress), $this->rawAddress($deliveryAddress));

        self::assertInstanceOf(ManifestShipment::class, $dispatched);
        self::assertSame($shipment->id->toString(), $dispatched->id);
        self::assertSame($trackingReference, $dispatched->trackingReference);
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
