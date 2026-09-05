<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipping\Application\ShipmentDirection;
use Fulfilment\Shipping\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestShipmentOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $buyerId = Uuid::uuid7()->toString();
        $destinationData = ShipmentBuilder::sample('destination')->toArray();
        $warehouseAddressProvider = $this->service(WarehouseAddressProvider::class);

        $dispatched = null;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(RequestShipmentOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: $orderId,
            buyerId: $buyerId,
            shippingAddress: $destinationData,
            confirmedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(RequestShipment::class, $dispatched);
        self::assertTrue(Uuid::isValid($dispatched->id));
        $originAddress = $warehouseAddressProvider->get()->toArray();
        self::assertSame($orderId, $dispatched->reference);
        self::assertSame(ShipmentDirection::OUTBOUND, $dispatched->direction);
        self::assertSame($buyerId, $dispatched->buyerId);
        self::assertSame($originAddress, $dispatched->origin);
        self::assertSame($destinationData, $dispatched->destination);
    }
}
