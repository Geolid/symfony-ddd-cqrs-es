<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\ValueObject;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ShipmentIdTest extends TestCase
{
    #[Test]
    public function itDerivesTheSameIdForTheSameOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $a = ShipmentId::forOrder($orderId);
        $b = ShipmentId::forOrder($orderId);

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentOrder(): void
    {
        // When
        $a = ShipmentId::forOrder(Uuid::uuid7()->toString());
        $b = ShipmentId::forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($a->equals($b));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        ShipmentId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid uuid' => ['not-a-uuid'];
    }
}
