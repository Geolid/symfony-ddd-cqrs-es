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
    public function itCreates(): void
    {
        // When
        $reference = PaymentReference::fromString('GLBX-9F3K2M1P');

        // Then
        self::assertSame('GLBX-9F3K2M1P', $reference->toString());
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

    #[Test]
    public function itAcceptsAReferenceAtTheMaximumLength(): void
    {
        // Given
        $value = str_repeat('A', 64);

        // When
        $reference = PaymentReference::fromString($value);

        // Then
        self::assertSame($value, $reference->toString());
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
}
