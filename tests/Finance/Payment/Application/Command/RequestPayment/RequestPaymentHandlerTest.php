<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentUniqueKey;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itRequestsForOrder(): void
    {
        // Given
        $paymentFactory = PaymentBuilder::new();
        $payment = $paymentFactory->create();

        // When
        $this->dispatch(new RequestPayment(
            id: $payment->id->toString(),
            orderId: $paymentFactory['orderId'],
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
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $paymentFactory = PaymentBuilder::new();
        $payment = $paymentFactory->create();
        $this->store($payment);

        // When
        $this->dispatch(new RequestPayment(
            id: $payment->id->toString(),
            orderId: $paymentFactory['orderId'],
            amountInCents: PaymentBuilder::sample('amount')->cents,
            reference: PaymentBuilder::sample('reference')->value,
            checkoutUrl: PaymentBuilder::sample('checkoutUrl'),
        ));

        // Then
        $result = $this->finder->ofReference($paymentFactory['reference']->value);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
    }

    #[Test]
    public function itFailsWhenReferenceAlreadyTaken(): void
    {
        // Given
        $orderId = PaymentBuilder::sample('orderId');
        $reference = PaymentBuilder::sample('reference')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(PaymentUniqueKey::REFERENCE), $reference, Uuid::uuid7()->toString());

        // Then
        $this->expectException(PaymentReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new RequestPayment(
            id: PaymentId::forOrder($orderId)->toString(),
            orderId: $orderId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }
}
