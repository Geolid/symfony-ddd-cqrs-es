<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\ValueObject\PaymentReference;

final class PaymentReferenceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        // When
        $reference = PaymentReference::fromString($value);

        // Then
        self::assertSame($value, $reference->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'reference' => ['GLBX-9F3K2M1P'];
        yield 'maximum length' => [str_repeat('A', 64)];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PaymentReference::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'longer than the provider can issue' => [str_repeat('A', 65)];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = 'GLBX-9F3K2M1P';

        // When
        $a = PaymentReference::fromString($value);
        $b = PaymentReference::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(PaymentReference::fromString('GLBX-OTHER')));
    }
}
