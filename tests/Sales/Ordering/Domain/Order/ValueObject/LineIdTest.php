<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\Order\ValueObject\LineId;

final class LineIdTest extends TestCase
{
    private const string ORDER_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = LineId::forOrder(self::ORDER_ID, 0);

        // Then
        self::assertSame('ccd61927-4a66-548f-8ea7-5a6cebb4bfdf', $id->toString());
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
        $a = LineId::forOrder(self::ORDER_ID, 0);
        $b = LineId::forOrder(self::ORDER_ID, 0);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = LineId::forOrder(self::ORDER_ID, 0);
        $b = LineId::forOrder(self::ORDER_ID, 1);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
