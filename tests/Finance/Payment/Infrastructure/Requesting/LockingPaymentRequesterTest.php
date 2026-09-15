<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Requesting;

use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Requesting\PaymentRequesterInterface;
use Finance\Payment\Infrastructure\Requesting\LockingPaymentRequester;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class LockingPaymentRequesterTest extends TestCase
{
    private PaymentRequesterInterface&MockObject $inner;
    private LockFactory&MockObject $lockFactory;
    private LockingPaymentRequester $requester;
    private \DateTimeImmutable $expiresAt;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(PaymentRequesterInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->requester = new LockingPaymentRequester($this->inner, $this->lockFactory);
        $this->expiresAt = Clock::get()->now()->modify('+30 minutes');
    }

    #[Test]
    public function itDelegatesToInnerRequester(): void
    {
        // Given
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lines = $this->lines();

        $this->lockFactory->expects($this->once())->method('createLock')
            ->with('finance.payment.payment_request.checkout-session-id', 30.0)
            ->willReturn($lock);

        $this->inner->expects($this->once())->method('requestFor')
            ->with('checkout-session-id', 'EUR', $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt)
            ->willReturn('https://checkout.globex.test/pay/GLBX-9F3K2M1P');

        // When
        $hostedPageUrl = $this->requester->requestFor('checkout-session-id', 'EUR', $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);

        // Then
        self::assertSame('https://checkout.globex.test/pay/GLBX-9F3K2M1P', $hostedPageUrl);
    }

    #[Test]
    public function itFailsWhenRequestInProgress(): void
    {
        // Given
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(false);

        $this->lockFactory->expects($this->once())->method('createLock')
            ->with('finance.payment.payment_request.checkout-session-id', 30.0)
            ->willReturn($lock);
        $this->inner->expects($this->never())->method('requestFor');

        // Then
        $this->expectException(PaymentRequestInProgressException::class);

        // When
        $this->requester->requestFor('checkout-session-id', 'EUR', $this->lines(), 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);
    }

    /**
     * @return list<PaymentLine>
     */
    private function lines(): array
    {
        return [
            new PaymentLine(Label::fromString('Espresso cups, set of 6'), Money::fromCents(4_200, 'EUR'), Quantity::of(1)),
        ];
    }
}
