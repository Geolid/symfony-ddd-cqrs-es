<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\InitiateOrderPaymentRefundOnWithdrawalApproved;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class InitiateOrderPaymentRefundOnWithdrawalApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiates(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);

        // When
        $this->trigger(
            InitiateOrderPaymentRefundOnWithdrawalApproved::class,
            new WithdrawalApprovedIntegrationEvent(
                WithdrawalId::forOrder($order->id->toString())->toString(),
                $order->id->toString(),
                Clock::get()->now(),
            ),
        );

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }
}
