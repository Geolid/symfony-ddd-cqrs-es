<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Uniqueness\Exception\PaymentReferenceAlreadyInUseException;
use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
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
    public function itFailsWhenReferenceAlreadyInUse(): void
    {
        // Given
        $cartId = PaymentBuilder::sample('cartId');
        $reference = PaymentBuilder::sample('reference')->value;
        $this->uniqueValues->claim(UniqueKey::for(PaymentUniqueKey::REFERENCE), $reference, Uuid::uuid7()->toString());

        // Then
        $this->expectException(PaymentReferenceAlreadyInUseException::class);

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
    public function itFailsWhenAlreadyClaimedForCart(): void
    {
        // Given
        $cartId = PaymentBuilder::sample('cartId');
        $this->uniqueValues->claim(UniqueKey::for(PaymentUniqueKey::CART), $cartId, Uuid::uuid7()->toString());
        $reference = PaymentBuilder::sample('reference')->value;

        // Then
        $this->expectException(PaymentAlreadyClaimedException::class);

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
