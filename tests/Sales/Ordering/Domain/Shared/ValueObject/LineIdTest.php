<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Shared\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;

final class LineIdTest extends TestCase
{
    private const string CART_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';
    private const string PRODUCT_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b8';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = LineId::forProduct(self::CART_ID, self::PRODUCT_ID);

        // Then
        self::assertSame('0618bff4-5647-5b06-8c79-f08c60e2821b', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        LineId::fromString($value);
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
        $a = LineId::forProduct(self::CART_ID, self::PRODUCT_ID);
        $b = LineId::forProduct(self::CART_ID, self::PRODUCT_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = LineId::forProduct(self::CART_ID, self::PRODUCT_ID);
        $b = LineId::forProduct(self::CART_ID, '0199a1b2-3c4d-7e5f-8061-72839405a6b9');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
