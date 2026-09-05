<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Finance\Refund\Application\Policy\InitiateRefundOnWithdrawalApproved;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class InitiateRefundOnWithdrawalApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiates(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new InitiateRefund($orderId));

        // When
        $this->trigger(InitiateRefundOnWithdrawalApproved::class, new WithdrawalApprovedIntegrationEvent(Uuid::uuid7()->toString(), $orderId, Clock::get()->now()));
    }
}
