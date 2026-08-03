<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\ValueObject\OrderId;

final class OrderIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = OrderId::generate();

        // Then
        self::assertNotEmpty($id->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = OrderId::generate()->toString();

        // When
        $a = OrderId::fromString($value);
        $b = OrderId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(OrderId::generate()));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        OrderId::fromString($value);
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
