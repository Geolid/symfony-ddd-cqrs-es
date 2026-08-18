<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RefundOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RefundOrderPayment\RefundOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RefundOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRefundsACapturedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->store();

        // When
        $this->dispatch(new RefundOrderPayment($orderPayment->id()->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUNDING, $result->status);
    }

    #[Test]
    public function itIgnoresAnUncapturedPayment(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->store();

        // When
        $this->dispatch(new RefundOrderPayment($orderPayment->id()->toString()));

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
        $this->dispatch(new RefundOrderPayment($id));
    }
}
