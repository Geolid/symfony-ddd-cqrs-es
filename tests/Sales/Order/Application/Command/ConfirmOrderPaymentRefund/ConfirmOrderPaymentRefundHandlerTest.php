<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ConfirmOrderPaymentRefund;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\ConfirmOrderPaymentRefund\ConfirmOrderPaymentRefund;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirmsRefundWhenInitiated(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->refundInitiated()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new ConfirmOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotRefunding(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
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
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new ConfirmOrderPaymentRefund($id));
    }
}
