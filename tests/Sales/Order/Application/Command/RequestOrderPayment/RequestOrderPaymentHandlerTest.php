<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Domain\OrderPaymentId;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Support\AbstractIntegrationTestCase;

final class RequestOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
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
            customerId: 'customer-1',
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-9F3K2M1P',
        ));

        // Then
        $orderPayment = $this->service(OrderPaymentRepositoryInterface::class)->load(OrderPaymentId::fromString($id));
        self::assertSame($orderId, $orderPayment->orderId());
        self::assertSame('GLBX-9F3K2M1P', $orderPayment->reference()->toString());
        self::assertTrue($orderPayment->status()->isRequested());
    }

    #[Test]
    public function itKeepsThePaymentItAlreadyRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            customerId: 'customer-1',
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-9F3K2M1P',
        ));

        // When
        $this->dispatch(new RequestOrderPayment(
            id: $id,
            orderId: $orderId,
            customerId: 'customer-1',
            buyerAddress: 'buyer@example.com',
            amountInCents: 4_200,
            reference: 'GLBX-OTHER',
        ));

        // Then
        $orderPayment = $this->service(OrderPaymentRepositoryInterface::class)->load(OrderPaymentId::fromString($id));
        self::assertSame('GLBX-9F3K2M1P', $orderPayment->reference()->toString());
    }
}
