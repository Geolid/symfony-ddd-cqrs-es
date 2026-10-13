<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\VoidPayment;

use Finance\Payment\Application\Command\VoidPayment\VoidPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
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
        $orderPayment = PaymentFactory::new()->authorized()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new VoidPayment($orderPayment->id->toString()));

        // Then
        $result = $this->finder->ofReference($orderPayment->reference->value);
        self::assertSame(PaymentStatus::VOIDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenRequested(): void
    {
        // Given
        $orderPayment = PaymentFactory::new()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new VoidPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // When
        $this->dispatch(new VoidPayment(PaymentIdFactory::new()->create()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
