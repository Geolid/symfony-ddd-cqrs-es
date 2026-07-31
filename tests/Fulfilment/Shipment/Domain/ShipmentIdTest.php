<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain;

use Fulfilment\Shipment\Domain\ShipmentId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShipmentIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ShipmentId::generate();

        // Then
        self::assertNotEmpty($id->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = ShipmentId::generate()->toString();

        // When
        $a = ShipmentId::fromString($value);
        $b = ShipmentId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(ShipmentId::generate()));
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
