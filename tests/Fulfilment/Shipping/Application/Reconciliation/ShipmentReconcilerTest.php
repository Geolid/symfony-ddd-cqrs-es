<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $unsupporting = $this->createMock(ShipmentStatusReconcilerInterface::class);
        $unsupporting->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('reconcile');

        $matching = $this->createStub(ShipmentStatusReconcilerInterface::class);
        $matching->method('supports')->willReturn(true);
        $matching->method('reconcile')->willReturn(true);

        $router = new ShipmentReconciler([$unsupporting, $matching]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::MANIFESTED, ShipmentBuilder::sample('trackingNumber')->value);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itFailsWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new ShipmentReconciler([]);

        // Then
        $this->expectException(UnsupportedShipmentStatusException::class);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::DISPATCHED, ShipmentBuilder::sample('trackingNumber')->value);
    }
}
