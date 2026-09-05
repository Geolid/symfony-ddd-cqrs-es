<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Domain\ValueObject;

use Finance\Refund\Domain\ValueObject\RefundId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RefundIdTest extends TestCase
{
    private const string PAYMENT_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = RefundId::forPayment(self::PAYMENT_ID);

        // Then
        self::assertSame('fbf4bcb3-1850-528f-9a2f-5520caf92b1a', $id->toString());
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
        $a = RefundId::forPayment(self::PAYMENT_ID);
        $b = RefundId::forPayment(self::PAYMENT_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = RefundId::forPayment(self::PAYMENT_ID);
        $b = RefundId::forPayment('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
