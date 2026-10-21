<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Reconciliation\Exception\UnsupportedPaymentStatusException;
use Finance\Payment\Application\Reconciliation\PaymentReconciler;
use Finance\Payment\Application\Reconciliation\PaymentStatusReconcilerInterface;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $reference = PaymentReferenceFactory::new()->create()->value;
        $unsupporting = $this->createMock(PaymentStatusReconcilerInterface::class);
        $unsupporting->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('reconcile');
        $matching = $this->createMock(PaymentStatusReconcilerInterface::class);
        $matching->method('supports')->willReturn(true);
        $matching->expects(self::once())->method('reconcile')->with($id, $reference);
        $router = new PaymentReconciler([$unsupporting, $matching]);

        // When
        $router->reconcile($id, PaymentStatus::REQUESTED, $reference);
    }

    #[Test]
    public function itFailsWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new PaymentReconciler([]);

        // Then
        $this->expectException(UnsupportedPaymentStatusException::class);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), PaymentStatus::CAPTURED, PaymentReferenceFactory::new()->create()->value);
    }
}
