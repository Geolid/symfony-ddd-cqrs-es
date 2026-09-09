<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\VoidPayment;

use Finance\Payment\Application\Command\VoidPayment\VoidPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $paymentBuilder = PaymentBuilder::new()->authorized();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new VoidPayment($orderPayment->id->toString()));

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::VOIDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenRequested(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->create();
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
        $this->dispatch(new VoidPayment(Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
