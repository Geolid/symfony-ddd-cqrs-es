<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Domain\ValueObject;

use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ProductId::generate();

        // Then
        self::assertNotEmpty($id->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = ProductId::generate()->toString();

        // When
        $a = ProductId::fromString($value);
        $b = ProductId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(ProductId::generate()));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        ProductId::fromString($value);
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
