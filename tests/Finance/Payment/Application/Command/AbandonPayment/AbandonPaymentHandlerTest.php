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
        $orderPayment = PaymentFactory::new()->create();
        $this->store($orderPayment);
        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);
        $this->uniqueness->claim($checkoutSessionKey, $orderPayment->checkoutSessionId, $orderPayment->id->toString());

        // When
        $this->dispatch(new AbandonPayment($orderPayment->id->toString()));

        // Then
        $result = $this->finder->ofReference($orderPayment->reference->value);
        self::assertSame(PaymentStatus::ABANDONED, $result->status);
        self::assertFalse($this->uniqueness->isClaimed($checkoutSessionKey, $orderPayment->checkoutSessionId));
    }

    #[Test]
    public function itIgnoresWhenAlreadyAbandoned(): void
    {
        // Given
        $orderPayment = PaymentFactory::new()->abandoned()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new AbandonPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = PaymentIdFactory::new()->create()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new AbandonPayment($id));
    }
}
