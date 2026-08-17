<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Application\Processor\RefundOrderPaymentOnOrderPaymentRefundRequested;
use Sales\Order\Domain\Event\OrderPaymentRefundRequested;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class RefundOrderPaymentOnOrderPaymentRefundRequestedTest extends AbstractIntegrationTestCase
{
    private RefundOrderPaymentOnOrderPaymentRefundRequested $processor;

    private SpyRefundingPaymentGateway $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new SpyRefundingPaymentGateway();
        self::getContainer()->set(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->processor = $this->service(RefundOrderPaymentOnOrderPaymentRefundRequested::class);
    }

    #[Test]
    public function itRefundsTheChargeOnOrderPaymentRefundRequested(): void
    {
        // Given
        $reference = 'GLBX-'.Uuid::uuid7()->toString();

        // When
        ($this->processor)(new OrderPaymentRefundRequested(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $reference, '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertSame($reference, $this->paymentGateway->refundedReference);
    }
}

final class SpyRefundingPaymentGateway implements PaymentGatewayInterface
{
    public ?string $refundedReference = null;

    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function void(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function refund(string $reference): void
    {
        $this->refundedReference = $reference;
    }
}
