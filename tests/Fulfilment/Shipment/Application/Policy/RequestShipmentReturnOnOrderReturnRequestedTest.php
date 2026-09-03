<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\RequestShipmentReturn\RequestShipmentReturn;
use Fulfilment\Shipment\Application\Policy\RequestShipmentReturnOnOrderReturnRequested;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderReturnRequested\OrderReturnRequestedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestShipmentReturnOnOrderReturnRequestedTest extends AbstractIntegrationTestCase
{
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $this->commandBus);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $dispatched = null;
        $this->commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(RequestShipmentReturnOnOrderReturnRequested::class, new OrderReturnRequestedIntegrationEvent($orderId, Clock::get()->now()));

        // Then
        self::assertInstanceOf(RequestShipmentReturn::class, $dispatched);
        self::assertSame(ShipmentId::forOrder($orderId)->toString(), $dispatched->id);
    }
}
