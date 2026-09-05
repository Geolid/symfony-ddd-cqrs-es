<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Manifest;

use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\PaymentCapture\PaymentCaptureFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Manifest\ShipmentManifester;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentManifesterTest extends AbstractIntegrationTestCase
{
    private CarrierGatewayInterface&MockObject $carrier;

    private ShipmentManifester $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = $this->createMock(CarrierGatewayInterface::class);
        $this->service = new ShipmentManifester(
            $this->service(ShipmentFinderInterface::class),
            $this->service(PaymentCaptureFinderInterface::class),
            $this->carrier,
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itManifestsWhenPaymentCaptured(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $payment = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $shipmentBuilder = ShipmentBuilder::new()->withReference($order->id->toString())->prepared();
        $shipment = $shipmentBuilder->create();
        $this->store($order, $payment, $shipment);
        $trackingNumber = ShipmentBuilder::sample('trackingNumber')->value;
        $this->carrier->expects(self::once())->method('manifest')
            ->with($shipment->id->toString(), $shipmentBuilder['origin'], $shipmentBuilder['destination'])
            ->willReturn($trackingNumber);

        // When
        $result = $this->service->manifest($shipment->id->toString());

        // Then
        self::assertSame($trackingNumber, $result);
    }

    #[Test]
    public function itManifestsWhenReturn(): void
    {
        // Given
        $shipmentBuilder = ShipmentBuilder::new()->withDirection(ShipmentDirection::RETURN)->prepared();
        $shipment = $shipmentBuilder->create();
        $this->store($shipment);
        $trackingNumber = ShipmentBuilder::sample('trackingNumber')->value;
        $this->carrier->expects(self::once())->method('manifest')->willReturn($trackingNumber);

        // When
        $result = $this->service->manifest($shipment->id->toString());

        // Then
        self::assertSame($trackingNumber, $result);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $this->carrier->expects(self::never())->method('manifest');

        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->service->manifest(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFailsWhenCancelled(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->cancelled()->create();
        $this->store($shipment);
        $this->carrier->expects(self::never())->method('manifest');

        // Then
        $this->expectException(ManifestDeniedException::class);

        // When
        $this->service->manifest($shipment->id->toString());
    }

    #[Test]
    public function itFailsWhenUncapturedPayment(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $payment = PaymentBuilder::new()->withOrderId($order->id->toString())->create();
        $shipment = ShipmentBuilder::new()->withReference($order->id->toString())->prepared()->create();
        $this->store($order, $payment, $shipment);
        $this->carrier->expects(self::never())->method('manifest');

        // Then
        $this->expectException(ManifestDeniedException::class);

        // When
        $this->service->manifest($shipment->id->toString());
    }
}
