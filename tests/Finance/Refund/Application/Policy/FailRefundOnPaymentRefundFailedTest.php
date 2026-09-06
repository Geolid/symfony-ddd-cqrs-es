<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundFailed\PaymentRefundFailedIntegrationEvent;
use Finance\Refund\Application\Command\FailRefund\FailRefund;
use Finance\Refund\Application\Policy\FailRefundOnPaymentRefundFailed;
use Finance\Refund\Domain\ValueObject\RefundId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class FailRefundOnPaymentRefundFailedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFails(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $refundId = RefundId::generate()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new FailRefund($refundId));

        // When
        $this->trigger(FailRefundOnPaymentRefundFailed::class, new PaymentRefundFailedIntegrationEvent($orderId, $refundId, Clock::get()->now()));
    }
}
