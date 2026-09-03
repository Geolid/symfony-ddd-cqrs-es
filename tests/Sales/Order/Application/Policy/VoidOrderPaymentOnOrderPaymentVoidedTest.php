<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Policy\VoidOrderPaymentOnOrderPaymentVoided;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class VoidOrderPaymentOnOrderPaymentVoidedTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);
    }

    #[Test]
    public function itVoids(): void
    {
        // Given
        $reference = 'GLBX-'.Uuid::uuid7()->toString();
        $this->paymentGateway->expects(self::once())->method('void')->with($reference);

        // When
        $this->trigger(VoidOrderPaymentOnOrderPaymentVoided::class, new OrderPaymentVoided(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $reference, Clock::get()->now()));
    }
}
