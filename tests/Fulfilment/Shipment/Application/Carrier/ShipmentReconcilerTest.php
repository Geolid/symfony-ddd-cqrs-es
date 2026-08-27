<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier;

use Fulfilment\Shipment\Application\Carrier\ShipmentReconciler;
use Fulfilment\Shipment\Application\Carrier\ShipmentStatusReconcilerInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelegatesToSupportingReconciler(): void
    {
        // Given
        $router = new ShipmentReconciler([new StubUnsupportingReconciler(), new StubMatchingReconciler()]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::MANIFESTED->value, 'ACME-4Q7X2K9', null);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itIgnoresWhenNoReconcilerSupportsStatus(): void
    {
        // Given
        $router = new ShipmentReconciler([]);

        // When
        $result = $router->reconcile(Uuid::uuid7()->toString(), ShipmentStatus::DISPATCHED->value, 'ACME-4Q7X2K9', null);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    #[DataProvider('provideReferenceResolution')]
    public function itForwardsReferenceForStatus(string $status, string $expectedReference): void
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
     * @return iterable<string, array{string, string}>
     */
    public static function provideReferenceResolution(): iterable
    {
        yield 'return manifested uses return tracking reference' => [ShipmentStatus::RETURN_MANIFESTED->value, 'ACME-RETURN-1'];
        yield 'return dispatched uses return tracking reference' => [ShipmentStatus::RETURN_DISPATCHED->value, 'ACME-RETURN-1'];
        yield 'dispatched uses tracking reference' => [ShipmentStatus::DISPATCHED->value, 'ACME-4Q7X2K9'];
    }
}

final class StubMatchingReconciler implements ShipmentStatusReconcilerInterface
{
    public function supports(string $status): bool
    {
        return ShipmentStatus::MANIFESTED->value === $status;
    }

    public function reconcile(string $id, string $reference): bool
    {
        return true;
    }
}

final class StubUnsupportingReconciler implements ShipmentStatusReconcilerInterface
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

final class SpyReconciler implements ShipmentStatusReconcilerInterface
{
    public ?string $receivedReference = null;

    public function __construct(private readonly string $supportedStatus)
    {
    }

    public function supports(string $status): bool
    {
        return $this->supportedStatus === $status;
    }

    public function reconcile(string $id, string $reference): bool
    {
        $this->receivedReference = $reference;

        return true;
    }
}
