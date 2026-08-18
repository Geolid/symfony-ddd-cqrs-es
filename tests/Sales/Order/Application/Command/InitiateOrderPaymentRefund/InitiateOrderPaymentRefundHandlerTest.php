<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\InitiateOrderPaymentRefund;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\InitiateOrderPaymentRefund\InitiateOrderPaymentRefund;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class InitiateOrderPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiatesARefundOnACapturedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->store();

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($orderPayment->id()->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }

    #[Test]
    public function itIgnoresAnUncapturedPayment(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->store();

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($orderPayment->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenThePaymentDoesNotExist(): void
    {
        // Given
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($id));
    }
}
