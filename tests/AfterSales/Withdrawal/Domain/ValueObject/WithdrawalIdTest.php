<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Domain\ValueObject;

use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WithdrawalIdTest extends TestCase
{
    private const string ORDER_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = WithdrawalId::forOrder(self::ORDER_ID);

        // Then
        self::assertSame('63e4bd4f-9fbd-5b2a-a37a-39ea595b003e', $id->toString());
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
        $a = WithdrawalId::forOrder(self::ORDER_ID);
        $b = WithdrawalId::forOrder(self::ORDER_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = WithdrawalId::forOrder(self::ORDER_ID);
        $b = WithdrawalId::forOrder('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
