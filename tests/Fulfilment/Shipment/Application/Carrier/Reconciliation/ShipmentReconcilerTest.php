<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipment\Application\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipment\Application\ShipmentStatus;
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
        $router = new ShipmentReconciler([new StubUnsupportingReconciler(), new StubMatchingReconciler()]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::MANIFESTED, 'ACME-4Q7X2K9', null);

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
        $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::DISPATCHED, 'ACME-4Q7X2K9', null);
    }

    #[Test]
    #[DataProvider('provideReferenceResolution')]
    public function itForwardsReferenceForStatus(ShipmentStatus $status, string $expectedReference): void
    {
        // Given
        $reconciler = new SpyReconciler($status);
        $router = new ShipmentReconciler([$reconciler]);

        // When
        $router->reconcile(Uuid::uuid7()->toString(), $status, 'ACME-4Q7X2K9', 'ACME-RETURN-1');

        // Then
        self::assertSame($expectedReference, $reconciler->receivedReference);
    }

    /**
     * @return iterable<string, array{ShipmentStatus, string}>
     */
    public static function provideReferenceResolution(): iterable
    {
        yield 'return manifested uses return tracking reference' => [ShipmentStatus::RETURN_MANIFESTED, 'ACME-RETURN-1'];
        yield 'return dispatched uses return tracking reference' => [ShipmentStatus::RETURN_DISPATCHED, 'ACME-RETURN-1'];
        yield 'dispatched uses tracking reference' => [ShipmentStatus::DISPATCHED, 'ACME-4Q7X2K9'];
    }
}

final class StubMatchingReconciler implements ShipmentStatusReconcilerInterface
{
    public function supports(ShipmentStatus $status): bool
    {
        return ShipmentStatus::MANIFESTED === $status;
    }

    public function reconcile(string $id, string $reference): bool
    {
        return true;
    }
}

final class StubUnsupportingReconciler implements ShipmentStatusReconcilerInterface
{
    public function supports(ShipmentStatus $status): bool
    {
        return false;
    }

    public function reconcile(string $id, string $reference): bool
    {
        throw new \LogicException('Not the supporting reconciler.');
    }
}

final class SpyReconciler implements ShipmentStatusReconcilerInterface
{
    public ?string $receivedReference = null;

    public function __construct(private readonly ShipmentStatus $supportedStatus)
    {
    }

    public function supports(ShipmentStatus $status): bool
    {
        return $this->supportedStatus === $status;
    }

    public function reconcile(string $id, string $reference): bool
    {
        $this->receivedReference = $reference;

        return true;
    }
}
