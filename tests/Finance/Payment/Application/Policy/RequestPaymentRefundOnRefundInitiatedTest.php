<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Command\RequestPaymentRefund\RequestPaymentRefund;
use Finance\Payment\Application\Policy\RequestPaymentRefundOnRefundInitiated;
use Finance\Refund\Application\IntegrationEvent\RefundInitiated\RefundInitiatedIntegrationEvent;
use Finance\Refund\Domain\ValueObject\RefundId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestPaymentRefundOnRefundInitiatedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequestsRefund(): void
    {
        // Given
        $refundId = RefundId::generate()->toString();
        $paymentId = Uuid::uuid7()->toString();
        $orderId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new RequestPaymentRefund($paymentId, $refundId));

        // When
        $this->trigger(RequestPaymentRefundOnRefundInitiated::class, new RefundInitiatedIntegrationEvent($refundId, $paymentId, $orderId, 4_200, Clock::get()->now()));
    }
}
