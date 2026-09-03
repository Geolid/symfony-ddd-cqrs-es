<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Policy\CancelShipmentOnOrderCancelled;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelShipmentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $this->commandBus);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withOrderId($orderId)->create();
        $this->store($shipment);

        $dispatched = null;
        $this->commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(CancelShipmentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Clock::get()->now()));

        // Then
        self::assertInstanceOf(CancelShipment::class, $dispatched);
        self::assertSame($shipment->id->toString(), $dispatched->id);
    }

    #[Test]
    public function itIgnoresWhenNoneExistForOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $this->commandBus->expects(self::never())->method('dispatch');

        // When
        $this->trigger(CancelShipmentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Clock::get()->now()));
    }
}
