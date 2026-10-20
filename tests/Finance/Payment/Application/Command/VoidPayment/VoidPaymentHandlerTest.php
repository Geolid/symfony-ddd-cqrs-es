<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\VoidPayment;

use Finance\Payment\Application\Command\VoidPayment\VoidPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentStateInconsistentException;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class VoidPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itVoidsWhenAuthorized(): void
    {
        // Given
        $payment = PaymentFactory::new()->authorized()->create();
        $this->store($payment);

        // When
        $this->dispatch(new VoidPayment($payment->id->toString()));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::VOIDED, $result->status);
    }

    #[Test]
    public function itFailsWhenRequested(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);

        // Then
        $this->expectException(PaymentStateInconsistentException::class);

        // When
        $this->dispatch(new VoidPayment($payment->id->toString()));
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $unknownId = PaymentIdFactory::new()->create()->toString();

        // When
        $this->dispatch(new VoidPayment($unknownId));

        // Then
        self::expectNotToPerformAssertions();
    }
}
