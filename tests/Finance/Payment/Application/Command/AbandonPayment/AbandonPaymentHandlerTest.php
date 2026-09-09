<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\AbandonPayment;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class AbandonPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itAbandonsWhenRequested(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $cartKey = UniqueKey::for(PaymentUniqueKey::CART);
        $this->uniqueValues->reserve($cartKey, $paymentBuilder['cartId'], $orderPayment->id->toString());

        // When
        $this->dispatch(new AbandonPayment($orderPayment->id->toString()));

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::ABANDONED, $result->status);
        self::assertFalse($this->uniqueValues->exists($cartKey, $paymentBuilder['cartId']));
    }

    #[Test]
    public function itIgnoresWhenAlreadyAbandoned(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->abandoned()->create();
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
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new AbandonPayment($id));
    }
}
