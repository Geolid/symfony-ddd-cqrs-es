<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Policy\RequestReturnShipmentOnWithdrawalRequested;
use Fulfilment\Shipment\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestReturnShipmentOnWithdrawalRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $withdrawalId = Uuid::uuid7()->toString();
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $originData = ShipmentBuilder::sample('origin')->toArray();
        $warehouseAddressProvider = $this->service(WarehouseAddressProvider::class);

        $dispatched = null;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(RequestReturnShipmentOnWithdrawalRequested::class, new WithdrawalRequestedIntegrationEvent(
            withdrawalId: $withdrawalId,
            orderId: $orderId,
            customerId: $customerId,
            shippingAddress: $originData,
            requestedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(RequestShipment::class, $dispatched);
        self::assertTrue(Uuid::isValid($dispatched->id));
        self::assertSame($withdrawalId, $dispatched->reference);
        self::assertSame($customerId, $dispatched->customerId);
        $destinationAddress = $warehouseAddressProvider->get()->toArray();
        self::assertSame($originData, $dispatched->origin);
        self::assertSame($destinationAddress, $dispatched->destination);
    }
}
