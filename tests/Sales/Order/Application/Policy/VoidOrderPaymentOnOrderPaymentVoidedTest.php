<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Application\Policy\VoidOrderPaymentOnOrderPaymentVoided;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class VoidOrderPaymentOnOrderPaymentVoidedTest extends AbstractIntegrationTestCase
{
    private VoidOrderPaymentOnOrderPaymentVoided $policy;

    private SpyVoidingPaymentGateway $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new SpyVoidingPaymentGateway();
        self::getContainer()->set(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->policy = $this->service(VoidOrderPaymentOnOrderPaymentVoided::class);
    }

    #[Test]
    public function itVoids(): void
    {
        // Given
        $reference = 'GLBX-'.Uuid::uuid7()->toString();

        // When
        ($this->policy)(new OrderPaymentVoided(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $reference, '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertSame($reference, $this->paymentGateway->voidedReference);
    }
}

final class SpyVoidingPaymentGateway implements PaymentGatewayInterface
{
    public ?string $voidedReference = null;

    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function void(string $reference): void
    {
        $this->voidedReference = $reference;
    }

    public function refund(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function checkStatus(string $reference): string
    {
        throw new \LogicException('Not needed by this test.');
    }
}
