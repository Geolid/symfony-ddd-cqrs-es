<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\ConfirmPaymentRefund;

use Finance\Payment\Application\Command\ConfirmPaymentRefund\ConfirmPaymentRefund;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConfirmPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirmsRefundWhenInitiated(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new ConfirmPaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(PaymentStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotRefunding(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new ConfirmPaymentRefund($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = PaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new ConfirmPaymentRefund($id));
    }
}
