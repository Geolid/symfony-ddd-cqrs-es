<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipment\Application\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $result = $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::MANIFESTED, ShipmentBuilder::sample('trackingReference')->value, null);

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
        $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::DISPATCHED, ShipmentBuilder::sample('trackingReference')->value, null);
    }

    #[Test]
    #[DataProvider('provideStatusUsingReturnReference')]
    public function itForwardsReferenceForStatus(ShipmentStatus $status, bool $usesReturnReference): void
    {
        // Given
        $trackingReference = ShipmentBuilder::sample('trackingReference')->value;
        $returnTrackingReference = ShipmentBuilder::sample('returnTrackingReference')->value;
        $expectedReference = $usesReturnReference ? $returnTrackingReference : $trackingReference;

        $reconciler = $this->createMock(ShipmentStatusReconcilerInterface::class);
        $reconciler->method('supports')->willReturn(true);
        $reconciler->expects(self::once())->method('reconcile')->with(self::anything(), $expectedReference)->willReturn(true);
        $router = new ShipmentReconciler([$reconciler]);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), $status, $trackingReference, $returnTrackingReference);
    }

    /**
     * @return iterable<string, array{ShipmentStatus, bool}>
     */
    public static function provideStatusUsingReturnReference(): iterable
    {
        yield 'return manifested uses return tracking reference' => [ShipmentStatus::RETURN_MANIFESTED, true];
        yield 'return dispatched uses return tracking reference' => [ShipmentStatus::RETURN_DISPATCHED, true];
        yield 'dispatched uses tracking reference' => [ShipmentStatus::DISPATCHED, false];
    }
}
