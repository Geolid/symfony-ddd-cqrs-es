<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\FailPayment;

use Finance\Payment\Application\Command\FailPayment\FailPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentNotFailableException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class FailPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itFailsWhenAuthorized(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->authorized()->create();
        $this->store($payment);

        // When
        $this->dispatch(new FailPayment($payment->id->toString(), $orderId));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
        self::assertSame($orderId, $result->orderId);
    }

    #[Test]
    public function itFailsWhenRequested(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);

        // Then
        $this->expectException(PaymentNotFailableException::class);

        // When
        $this->dispatch(new FailPayment($payment->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = PaymentIdFactory::new()->create()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new FailPayment($unknownId, Uuid::uuid7()->toString()));
    }
}
