<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\AbandonPayment;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class AbandonPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itAbandonsWhenRequested(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);
        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);
        $this->uniqueness->claim($checkoutSessionKey, $payment->checkoutSessionId, $payment->id->toString());

        // When
        $this->dispatch(new AbandonPayment($payment->id->toString()));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::ABANDONED, $result->status);
        self::assertFalse($this->uniqueness->isClaimed($checkoutSessionKey, $payment->checkoutSessionId));
    }

    #[Test]
    public function itIgnoresWhenAlreadyAbandoned(): void
    {
        // Given
        $payment = PaymentFactory::new()->abandoned()->create();
        $this->store($payment);

        // When
        $this->dispatch(new AbandonPayment($payment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = PaymentIdFactory::new()->create()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new AbandonPayment($unknownId));
    }
}
