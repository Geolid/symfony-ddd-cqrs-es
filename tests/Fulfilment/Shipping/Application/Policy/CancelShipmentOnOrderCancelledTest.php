<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipping\Application\Policy\CancelShipmentOnOrderCancelled;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelShipmentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new CancelShipment($orderId));

        // When
        $this->trigger(CancelShipmentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Uuid::uuid7()->toString(), Clock::get()->now()));
    }
}
