<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\Exception\UnsupportedPaymentStatusException;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Reconciliation\PaymentReconciler;
use Finance\Payment\Application\Reconciliation\PaymentStatusReconcilerInterface;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $unsupporting = $this->createMock(PaymentStatusReconcilerInterface::class);
        $unsupporting->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('reconcile');

        $matching = $this->createStub(PaymentStatusReconcilerInterface::class);
        $matching->method('supports')->willReturn(true);
        $matching->method('reconcile')->willReturn(true);

        $router = new PaymentReconciler([$unsupporting, $matching]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), PaymentStatus::REQUESTED, PaymentBuilder::sample('reference')->value);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itFailsWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new PaymentReconciler([]);

        // Then
        $this->expectException(UnsupportedPaymentStatusException::class);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), PaymentStatus::CAPTURED, PaymentBuilder::sample('reference')->value);
    }
}
