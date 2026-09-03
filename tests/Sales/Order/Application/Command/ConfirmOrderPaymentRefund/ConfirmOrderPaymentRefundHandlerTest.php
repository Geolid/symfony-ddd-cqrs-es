<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ConfirmOrderPaymentRefund;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrderPaymentRefund\ConfirmOrderPaymentRefund;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConfirmOrderPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirmsRefundWhenInitiated(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new ConfirmOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentFactory->attribute('reference')->value);
        self::assertSame(OrderPaymentStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotRefunding(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new ConfirmOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentBuilder::new()->create()->id->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new ConfirmOrderPaymentRefund($id));
    }
}
