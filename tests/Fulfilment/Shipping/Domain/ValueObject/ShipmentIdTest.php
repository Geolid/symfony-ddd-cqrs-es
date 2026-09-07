<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Domain\ValueObject;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ShipmentIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ShipmentId::fromString(Uuid::uuid7()->toString());

        // Then
        self::assertTrue(Uuid::isValid($id->toString()));
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
    public function itEquals(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $a = ShipmentId::fromString($value);
        $b = ShipmentId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = ShipmentId::fromString(Uuid::uuid7()->toString());
        $b = ShipmentId::fromString(Uuid::uuid7()->toString());

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
