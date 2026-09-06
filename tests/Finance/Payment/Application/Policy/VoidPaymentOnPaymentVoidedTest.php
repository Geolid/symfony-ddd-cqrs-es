<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Policy\VoidPaymentOnPaymentVoided;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class VoidPaymentOnPaymentVoidedTest extends AbstractIntegrationTestCase
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
        $reference = PaymentBuilder::sample('reference');
        $this->paymentGateway->expects(self::once())->method('void')->with($reference->value)->willReturn(PaymentGatewayStatus::VOIDED);

        // When
        $this->trigger(VoidPaymentOnPaymentVoided::class, new PaymentVoided(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $reference, Clock::get()->now()));
    }
}
