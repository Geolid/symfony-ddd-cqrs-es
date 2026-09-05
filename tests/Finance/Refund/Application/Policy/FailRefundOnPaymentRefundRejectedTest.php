<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected\PaymentRefundRejectedIntegrationEvent;
use Finance\Refund\Application\Command\FailRefund\FailRefund;
use Finance\Refund\Application\Policy\FailRefundOnPaymentRefundRejected;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class FailRefundOnPaymentRefundRejectedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFails(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new FailRefund($orderId));

        // When
        $this->trigger(FailRefundOnPaymentRefundRejected::class, new PaymentRefundRejectedIntegrationEvent($orderId, Clock::get()->now()));
    }
}
