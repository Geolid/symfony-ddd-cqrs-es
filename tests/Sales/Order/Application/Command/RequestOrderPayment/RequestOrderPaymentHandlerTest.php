<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentUniqueValue;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class RequestOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    private OrderPaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderPaymentRepositoryInterface::class);
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
            customerId: Uuid::uuid7()->toString(),
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-9F3K2M1P',
            checkoutUrl: 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
        ));

        // Then
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($id));
        self::assertSame('GLBX-9F3K2M1P', $orderPayment->reference()->toString());
        self::assertTrue($orderPayment->status()->isRequested());
    }

    #[Test]
    public function itKeepsThePaymentItAlreadyRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            customerId: $customerId,
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-9F3K2M1P',
            checkoutUrl: 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
        ));

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            customerId: $customerId,
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-OTHER',
            checkoutUrl: 'https://fake-checkout.test/?ref=GLBX-OTHER',
        ));

        // Then
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($id));
        self::assertSame('GLBX-9F3K2M1P', $orderPayment->reference()->toString());
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
            customerId: Uuid::uuid7()->toString(),
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: $reference,
            checkoutUrl: \sprintf('https://fake-checkout.test/?ref=%s', $reference),
        ));
    }
}
