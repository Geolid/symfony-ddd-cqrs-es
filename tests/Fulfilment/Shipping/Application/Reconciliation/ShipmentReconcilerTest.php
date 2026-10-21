<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $reference = TrackingNumberFactory::new()->create()->value;
        $unsupporting = $this->createMock(ShipmentStatusReconcilerInterface::class);
        $unsupporting->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('reconcile');
        $matching = $this->createMock(ShipmentStatusReconcilerInterface::class);
        $matching->method('supports')->willReturn(true);
        $matching->expects(self::once())->method('reconcile')->with($id, $reference);
        $router = new ShipmentReconciler([$unsupporting, $matching]);

        // When
        $router->reconcile($id, ShipmentStatus::MANIFESTED, $reference);
    }

    #[Test]
    public function itFailsWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new ShipmentReconciler([]);

        // Then
        $this->expectException(UnsupportedShipmentStatusException::class);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::DISPATCHED, TrackingNumberFactory::new()->create()->value);
    }
}
