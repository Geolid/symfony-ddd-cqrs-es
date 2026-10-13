<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyInUseException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestPaymentHandlerTest extends AbstractIntegrationTestCase
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
    public function itRequests(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();

        // When
        $this->dispatch(new RequestPayment(
            id: $payment->id->toString(),
            checkoutSessionId: $payment->checkoutSessionId,
            amountInCents: $payment->amount->cents,
            currency: $payment->amount->currency->value,
            reference: $payment->reference->value,
            hostedPageUrl: $payment->hostedPageUrl,
        ));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame($payment->reference->value, $result->reference);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenReferenceAlreadyInUse(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $reference = PaymentReferenceFactory::new()->create()->value;
        $this->uniqueness->claim(UniqueKey::for(PaymentUniqueKey::REFERENCE), $reference, Uuid::uuid7()->toString());

        // Then
        $this->expectException(PaymentReferenceAlreadyInUseException::class);

        // When
        $this->dispatch(new RequestPayment(
            id: Uuid::uuid7()->toString(),
            checkoutSessionId: $checkoutSessionId,
            amountInCents: 4_200,
            currency: 'EUR',
            reference: $reference,
            hostedPageUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyClaimedForCheckoutSession(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $this->uniqueness->claim(UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION), $checkoutSessionId, Uuid::uuid7()->toString());
        $reference = PaymentReferenceFactory::new()->create()->value;

        // Then
        $this->expectException(PaymentAlreadyClaimedException::class);

        // When
        $this->dispatch(new RequestPayment(
            id: Uuid::uuid7()->toString(),
            checkoutSessionId: $checkoutSessionId,
            amountInCents: 4_200,
            currency: 'EUR',
            reference: $reference,
            hostedPageUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }
}
