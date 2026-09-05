<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Policy\RefundPaymentOnPaymentRefundRequired;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RefundPaymentOnPaymentRefundRequiredTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);
    }

    #[Test]
    public function itRefunds(): void
    {
        // Given
        $reference = 'GLBX-'.Uuid::uuid7()->toString();
        $this->paymentGateway->expects(self::once())->method('refund')->with($reference);

        // When
        $this->trigger(RefundPaymentOnPaymentRefundRequired::class, new PaymentRefundRequired(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $reference, Clock::get()->now()));
    }
}
