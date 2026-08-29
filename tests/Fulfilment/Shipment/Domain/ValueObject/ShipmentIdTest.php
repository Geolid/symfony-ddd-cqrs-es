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
    public function itDerivesKnownId(): void
    {
        // When
        $id = ShipmentId::forOrder('0199a1b2-3c4d-7e5f-8061-72839405a6b7');

        // Then
        self::assertSame('df2b83ff-c193-53b1-b4f0-ebba2dd89d08', $id->toString());
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

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $a = ShipmentId::forOrder($orderId);
        $b = ShipmentId::forOrder($orderId);
        $other = ShipmentId::forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }
}
