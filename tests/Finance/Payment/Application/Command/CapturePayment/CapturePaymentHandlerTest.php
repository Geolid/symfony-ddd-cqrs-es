<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\CapturePayment;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class CapturePaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->authorized()->create();
        $this->store($payment);

        // When
        $this->dispatch(new CapturePayment($payment->id->toString(), $orderId));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
        self::assertSame($orderId, $result->orderId);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCaptured(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->authorized()->captured()->create();
        $this->store($payment);

        // When
        $this->dispatch(new CapturePayment($payment->id->toString(), $orderId));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
        self::assertNotSame($orderId, $result->orderId);
    }
}
