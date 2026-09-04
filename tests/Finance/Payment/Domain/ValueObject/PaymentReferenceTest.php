<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain\ValueObject;

use Finance\Payment\Domain\ValueObject\PaymentReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
        yield 'maximum length' => [str_repeat('A', PaymentReference::MAX_LENGTH)];
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
        yield 'too long' => [str_repeat('A', PaymentReference::MAX_LENGTH + 1)];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $value = 'GLBX-9F3K2M1P';
        $a = PaymentReference::fromString($value);
        $b = PaymentReference::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = PaymentReference::fromString('GLBX-9F3K2M1P');
        $b = PaymentReference::fromString('GLBX-OTHER');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
