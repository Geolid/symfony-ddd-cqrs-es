<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Payment\OrderPaymentReconciler;
use Sales\Order\Application\Payment\OrderPaymentStatusReconcilerInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToTheSupportingReconciler(): void
    {
        // Given
        $router = new OrderPaymentReconciler([new StubUnsupportingReconciler(), new StubMatchingReconciler()]);

        // When
        $result = $router->reconcile('order-payment-id', OrderPaymentStatus::REQUESTED->value, 'GLBX-0001');

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itIgnoresWhenNoReconcilerSupportsTheStatus(): void
    {
        // Given
        $router = new OrderPaymentReconciler([]);

        // When
        $result = $router->reconcile('order-payment-id', OrderPaymentStatus::CAPTURED->value, 'GLBX-0001');

        // Then
        self::assertFalse($result);
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
