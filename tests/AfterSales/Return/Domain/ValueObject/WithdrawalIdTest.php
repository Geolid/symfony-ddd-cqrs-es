<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Domain\ValueObject;

use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class WithdrawalIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = WithdrawalId::generate();

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
        WithdrawalId::fromString($value);
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
        $value = WithdrawalId::generate()->toString();
        $a = WithdrawalId::fromString($value);
        $b = WithdrawalId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = WithdrawalId::generate();
        $b = WithdrawalId::generate();

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
