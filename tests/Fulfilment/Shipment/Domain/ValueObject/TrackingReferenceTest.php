<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\ValueObject;

use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TrackingReferenceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        // When
        $reference = TrackingReference::fromString($value);

        // Then
        self::assertSame($value, $reference->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'reference' => ['ACME-4Q7X2K9'];
        yield 'maximum length' => [str_repeat('A', TrackingReference::MAX_LENGTH)];
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $reference = TrackingReference::fromString('  ACME-4Q7X2K9  ');

        // Then
        self::assertSame('ACME-4Q7X2K9', $reference->value);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        TrackingReference::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'longer than the carrier can issue' => [str_repeat('A', TrackingReference::MAX_LENGTH + 1)];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = TrackingReference::fromString('ACME-4Q7X2K9');
        $b = TrackingReference::fromString('ACME-4Q7X2K9');
        $other = TrackingReference::fromString('ACME-OTHER');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }
}
