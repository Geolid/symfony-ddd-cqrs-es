<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain\ValueObject;

use Finance\Payment\Domain\ValueObject\PaymentId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaymentIdTest extends TestCase
{
    private const string ORDER_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = PaymentId::forOrder(self::ORDER_ID);

        // Then
        self::assertSame('35a17cdc-4f76-5c72-93c7-4ec8000e0c08', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PaymentId::fromString($value);
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
        $a = PaymentId::forOrder(self::ORDER_ID);
        $b = PaymentId::forOrder(self::ORDER_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = PaymentId::forOrder(self::ORDER_ID);
        $b = PaymentId::forOrder('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
