<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentUniqueKey;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class RequestOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itRequestsForOrder(): void
    {
        // Given
        $paymentFactory = OrderPaymentBuilder::new();
        $payment = $paymentFactory->create();
        $orderId = $paymentFactory->attribute('orderId');
        $reference = $paymentFactory->attribute('reference')->value;

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $payment->id->toString(),
            orderId: $orderId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));

        // Then
        $result = $this->finder->ofReference($reference);
        self::assertSame($reference, $result->reference);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $paymentFactory = OrderPaymentBuilder::new();
        $payment = $paymentFactory->create();
        $orderId = $paymentFactory->attribute('orderId');
        $reference = $paymentFactory->attribute('reference')->value;
        $this->store($payment);

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $payment->id->toString(),
            orderId: $orderId,
            amountInCents: 4_200,
            reference: 'GLBX-OTHER',
            checkoutUrl: 'https://checkout.globex.test/pay/GLBX-OTHER',
        ));

        // Then
        $result = $this->finder->ofReference($reference);
        self::assertSame($reference, $result->reference);
    }

    #[Test]
    public function itFailsWhenReferenceAlreadyTaken(): void
    {
        // Given
        $paymentFactory = OrderPaymentBuilder::new();
        $orderId = $paymentFactory->attribute('orderId');
        $reference = $paymentFactory->attribute('reference')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(OrderPaymentUniqueKey::REFERENCE), $reference, Uuid::uuid7()->toString());

        // Then
        $this->expectException(PaymentReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new RequestOrderPayment(
            id: OrderPaymentId::forOrder($orderId)->toString(),
            orderId: $orderId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://checkout.globex.test/pay/%s', $reference),
        ));
    }
}
