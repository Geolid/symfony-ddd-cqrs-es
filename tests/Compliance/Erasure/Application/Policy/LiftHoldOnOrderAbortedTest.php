<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftHold\LiftHold;
use Compliance\Erasure\Application\Policy\LiftHoldOnOrderAborted;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderAborted\OrderAbortedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class LiftHoldOnOrderAbortedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLifts(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $buyerId = Uuid::uuid7()->toString();

        $dispatched = null;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(LiftHoldOnOrderAborted::class, new OrderAbortedIntegrationEvent(
            orderId: $orderId,
            buyerId: $buyerId,
            abortedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(LiftHold::class, $dispatched);
        self::assertSame($buyerId, $dispatched->subjectId);
        self::assertSame('sales.order.order', $dispatched->sourceType);
        self::assertSame($orderId, $dispatched->sourceId);
    }
}
