<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Checkout;

use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Checkout\PaymentRequesterInterface;
use Finance\Payment\Infrastructure\Checkout\LockingPaymentRequester;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class LockingPaymentRequesterTest extends TestCase
{
    private PaymentRequesterInterface&MockObject $inner;
    private LockFactory&MockObject $lockFactory;
    private LockingPaymentRequester $requester;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(PaymentRequesterInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->requester = new LockingPaymentRequester($this->inner, $this->lockFactory);
    }

    #[Test]
    public function itDelegatesToInnerRequester(): void
    {
        // Given
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);

        $this->lockFactory->expects($this->once())->method('createLock')
            ->with('finance.payment.payment_request.order-id', 30.0)
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
            ->with('finance.payment.payment_request.order-id', 30.0)
            ->willReturn($lock);
        $this->inner->expects($this->never())->method('requestFor');

        // Then
        $this->expectException(PaymentRequestInProgressException::class);

        // When
        $this->requester->requestFor('order-id', 'https://web.test/sales/orders');
    }
}
