<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\UnsupportedOrderPaymentStatusException;
use Sales\Order\Application\Payment\OrderPaymentReconciler;
use Sales\Order\Application\Payment\OrderPaymentStatusReconcilerInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $router = new OrderPaymentReconciler([new StubUnsupportingReconciler(), new StubMatchingReconciler()]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), OrderPaymentStatus::REQUESTED->value, 'GLBX-9F3K2M1P');

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itFailsWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new OrderPaymentReconciler([]);

        // Then
        $this->expectException(UnsupportedOrderPaymentStatusException::class);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), OrderPaymentStatus::CAPTURED->value, 'GLBX-9F3K2M1P');
    }
}

final class StubMatchingReconciler implements OrderPaymentStatusReconcilerInterface
{
    public function supports(string $status): bool
    {
        return OrderPaymentStatus::REQUESTED->value === $status;
    }

    public function reconcile(string $id, string $reference): bool
    {
        return true;
    }
}

final class StubUnsupportingReconciler implements OrderPaymentStatusReconcilerInterface
{
    public function supports(string $status): bool
    {
        return false;
    }

    public function reconcile(string $id, string $reference): bool
    {
        throw new \LogicException('Not the supporting reconciler.');
    }
}
