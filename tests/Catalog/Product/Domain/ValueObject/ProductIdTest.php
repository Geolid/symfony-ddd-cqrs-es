<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Domain\ValueObject;

use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ProductIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ProductId::generate();

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

    #[Test]
    public function itEquals(): void
    {
        // Given
        $value = ProductId::generate()->toString();
        $a = ProductId::fromString($value);
        $b = ProductId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = ProductId::generate();
        $b = ProductId::generate();

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
