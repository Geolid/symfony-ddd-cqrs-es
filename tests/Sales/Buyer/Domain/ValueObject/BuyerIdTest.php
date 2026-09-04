<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Domain\ValueObject\BuyerId;

final class BuyerIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = BuyerId::generate();

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
        BuyerId::fromString($value);
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
        $value = BuyerId::generate()->toString();
        $a = BuyerId::fromString($value);
        $b = BuyerId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = BuyerId::generate();
        $b = BuyerId::generate();

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
