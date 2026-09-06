<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Domain\ValueObject;

use Finance\Refund\Domain\ValueObject\RefundId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class RefundIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = RefundId::generate();

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
        RefundId::fromString($value);
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
        $value = RefundId::generate()->toString();
        $a = RefundId::fromString($value);
        $b = RefundId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = RefundId::generate();
        $b = RefundId::generate();

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
