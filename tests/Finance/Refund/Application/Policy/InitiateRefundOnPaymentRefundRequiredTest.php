<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRequired\PaymentRefundRequiredIntegrationEvent;
use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Finance\Refund\Application\Policy\InitiateRefundOnPaymentRefundRequired;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class InitiateRefundOnPaymentRefundRequiredTest extends AbstractIntegrationTestCase
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
        $this->trigger(InitiateRefundOnPaymentRefundRequired::class, new PaymentRefundRequiredIntegrationEvent($orderId, Clock::get()->now()));
    }
}
