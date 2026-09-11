<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipping\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestShipmentOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shopperId = Uuid::uuid7()->toString();
        $destinationData = PostalAddressMapper::toArray(ShipmentBuilder::sample('destination'));
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
            cartId: Uuid::uuid7()->toString(),
            shopperId: $shopperId,
            paymentId: Uuid::uuid7()->toString(),
            shippingAddress: $destinationData,
            confirmedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(RequestShipment::class, $dispatched);
        self::assertTrue(Uuid::isValid($dispatched->id));
        $originAddress = PostalAddressMapper::toArray($warehouseAddressProvider->get());
        self::assertSame($orderId, $dispatched->orderId);
        self::assertSame($shopperId, $dispatched->shopperId);
        self::assertSame($originAddress, $dispatched->origin);
        self::assertSame($destinationData, $dispatched->destination);
    }
}
