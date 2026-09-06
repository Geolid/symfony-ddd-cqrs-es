<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Policy\VoidPaymentOnPaymentVoided;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\ValueObject\PaymentReference;
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
        $reference = 'GLBX-'.Uuid::uuid7()->toString();
        $this->paymentGateway->expects(self::once())->method('void')->with($reference);

        // When
        $this->trigger(VoidPaymentOnPaymentVoided::class, new PaymentVoided(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), PaymentReference::fromString($reference), Clock::get()->now()));
    }
}
