<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\InitiatePaymentRefundOnWithdrawalApproved;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class InitiatePaymentRefundOnWithdrawalApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiates(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);

        // When
        $this->trigger(
            InitiatePaymentRefundOnWithdrawalApproved::class,
            new WithdrawalApprovedIntegrationEvent(
                WithdrawalId::forOrder($order->id->toString())->toString(),
                $order->id->toString(),
                Clock::get()->now(),
            ),
        );

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::REFUND_INITIATED, $result->status);
    }
}
