<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Domain\ValueObject;

use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TrackingNumberTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        // When
        $number = TrackingNumber::fromString($value);

        // Then
        self::assertSame($value, $number->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'value' => ['ACME-REFERENCE'];
        yield 'maximum length' => [str_repeat('A', TrackingNumber::MAX_LENGTH)];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        TrackingNumber::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('A', TrackingNumber::MAX_LENGTH + 1)];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = TrackingNumber::fromString('ACME-REFERENCE');
        $b = TrackingNumber::fromString('  ACME-REFERENCE  ');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = TrackingNumber::fromString('ACME-REFERENCE');
        $b = TrackingNumber::fromString('ACME-OTHER');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
