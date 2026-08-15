<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentUniqueValue;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Shared\Domain\Service\UniqueValueRegistryInterface;
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
    public function itRequestsAPaymentForAnOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            amountInCents: 4_200,
            reference: 'GLBX-9F3K2M1P',
            checkoutUrl: 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
        ));

        // Then
        $result = $this->finder->ofOrderOrNull($orderId);
        self::assertNotNull($result);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itKeepsThePaymentItAlreadyRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(4_200)
            ->withReference('GLBX-9F3K2M1P')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->store();

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            amountInCents: 4_200,
            reference: 'GLBX-OTHER',
            checkoutUrl: 'https://fake-checkout.test/?ref=GLBX-OTHER',
        ));

        // Then
        $result = $this->finder->ofOrderOrNull($orderId);
        self::assertNotNull($result);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
    }

    #[Test]
    public function itFailsWhenTheReferenceIsAlreadyTaken(): void
    {
        // Given
        $reference = 'GLBX-9F3K2M1P';
        $this->service(UniqueValueRegistryInterface::class)->reserve(OrderPaymentUniqueValue::REFERENCE, $reference);
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new RequestOrderPayment(
            id: OrderPaymentId::forOrder($orderId)->toString(),
            orderId: $orderId,
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://fake-checkout.test/?ref=%s', $reference),
        ));
    }
}
