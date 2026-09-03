<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment\Reconciliation;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\UnsupportedOrderPaymentStatusException;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Payment\Reconciliation\OrderPaymentReconciler;
use Sales\Order\Application\Payment\Reconciliation\OrderPaymentStatusReconcilerInterface;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $unsupporting = $this->createMock(OrderPaymentStatusReconcilerInterface::class);
        $unsupporting->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('reconcile');

        $matching = $this->createStub(OrderPaymentStatusReconcilerInterface::class);
        $matching->method('supports')->willReturn(true);
        $matching->method('reconcile')->willReturn(true);

        $router = new OrderPaymentReconciler([$unsupporting, $matching]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), OrderPaymentStatus::REQUESTED, OrderPaymentBuilder::sample('reference')->value);

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
        $router->reconcile(Uuid::uuid7()->toString(), OrderPaymentStatus::CAPTURED, OrderPaymentBuilder::sample('reference')->value);
    }
}
