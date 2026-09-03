<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Payment;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Infrastructure\Payment\LockingOrderPaymentRequester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class LockingOrderPaymentRequesterTest extends TestCase
{
    private OrderPaymentRequesterInterface&MockObject $inner;
    private LockFactory&MockObject $lockFactory;
    private LockingOrderPaymentRequester $requester;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(OrderPaymentRequesterInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->requester = new LockingOrderPaymentRequester($this->inner, $this->lockFactory);
    }

    #[Test]
    public function itDelegatesToInnerRequester(): void
    {
        // Given
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);

        $this->lockFactory->expects($this->once())->method('createLock')
            ->with('sales.order.payment_request.order-id', 30.0)
            ->willReturn($lock);

        $this->inner->expects($this->once())->method('requestFor')
            ->with('order-id', 'https://web.test/sales/orders')
            ->willReturn('https://checkout.globex.test/pay/GLBX-9F3K2M1P');

        // When
        $checkoutUrl = $this->requester->requestFor('order-id', 'https://web.test/sales/orders');

        // Then
        self::assertSame('https://checkout.globex.test/pay/GLBX-9F3K2M1P', $checkoutUrl);
    }

    #[Test]
    public function itFailsWhenRequestInProgress(): void
    {
        // Given
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(false);

        $this->lockFactory->expects($this->once())->method('createLock')
            ->with('sales.order.payment_request.order-id', 30.0)
            ->willReturn($lock);
        $this->inner->expects($this->never())->method('requestFor');

        // Then
        $this->expectException(OrderPaymentRequestInProgressException::class);

        // When
        $this->requester->requestFor('order-id', 'https://web.test/sales/orders');
    }
}
