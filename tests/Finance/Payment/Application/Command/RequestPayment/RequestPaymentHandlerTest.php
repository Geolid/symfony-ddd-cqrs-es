<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Uniqueness\Exception\PaymentAlreadyRequestedException;
use Finance\Payment\Application\Uniqueness\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestPaymentHandlerTest extends AbstractIntegrationTestCase
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
    public function itRequests(): void
    {
        // Given
        $paymentFactory = PaymentBuilder::new();
        $payment = $paymentFactory->create();

        // When
        $this->dispatch(new RequestPayment(
            id: $payment->id->toString(),
            cartId: $paymentFactory['cartId'],
            amountInCents: $paymentFactory['amount']->cents,
            reference: $paymentFactory['reference']->value,
            checkoutUrl: $paymentFactory['checkoutUrl'],
        ));

        // Then
        $result = $this->finder->ofReference($paymentFactory['reference']->value);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenReferenceAlreadyTaken(): void
    {
        // Given
        $cartId = PaymentBuilder::sample('cartId');
        $reference = PaymentBuilder::sample('reference')->value;
        $this->uniqueValues->reserve(UniqueKey::for(PaymentUniqueKey::REFERENCE), $reference, Uuid::uuid7()->toString());

        // Then
        $this->expectException(PaymentReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new RequestPayment(
            id: Uuid::uuid7()->toString(),
            cartId: $cartId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyRequestedForCart(): void
    {
        // Given
        $cartId = PaymentBuilder::sample('cartId');
        $this->uniqueValues->reserve(UniqueKey::for(PaymentUniqueKey::CART), $cartId, Uuid::uuid7()->toString());
        $reference = PaymentBuilder::sample('reference')->value;

        // Then
        $this->expectException(PaymentAlreadyRequestedException::class);

        // When
        $this->dispatch(new RequestPayment(
            id: Uuid::uuid7()->toString(),
            cartId: $cartId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }
}
