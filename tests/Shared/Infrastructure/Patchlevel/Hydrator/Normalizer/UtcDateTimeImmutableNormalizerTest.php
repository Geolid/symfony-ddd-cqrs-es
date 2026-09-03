<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\UtcDateTimeImmutableNormalizer;
use Symfony\Component\Clock\Clock;

final class UtcDateTimeImmutableNormalizerTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private UtcDateTimeImmutableNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new UtcDateTimeImmutableNormalizer();
    }

    #[Test]
    #[DataProvider('provideNormalizableValues')]
    public function itNormalizes(?\DateTimeImmutable $value, ?string $expected): void
    {
        // When
        $normalized = $this->normalizer->normalize($value);

        // Then
        self::assertSame($expected, $normalized);
    }

    /**
     * @return iterable<string, array{?\DateTimeImmutable, ?string}>
     */
    public static function provideNormalizableValues(): iterable
    {
        $date = Clock::get()->now();

        yield 'date' => [$date, $date->format(self::DATE_FORMAT)];
        yield 'null' => [null, null];
    }

    #[Test]
    public function itThrowsWhenNormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->normalize('not-a-date');
    }

    #[Test]
    public function itDenormalizes(): void
    {
        // Given
        $formatted = Clock::get()->now()->format(self::DATE_FORMAT);

        // When
        $date = $this->normalizer->denormalize($formatted);

        // Then
        self::assertNotNull($date);
        self::assertSame($formatted, $date->format(self::DATE_FORMAT));
        self::assertSame('UTC', $date->getTimezone()->getName());
    }

    #[Test]
    public function itDenormalizesNull(): void
    {
        // When
        $value = $this->normalizer->denormalize(null);

        // Then
        self::assertNull($value);
    }

    #[Test]
    public function itThrowsWhenDenormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->denormalize(1772980200);
    }

    #[Test]
    public function itThrowsWhenDenormalizingInvalidFormat(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->denormalize('not a date');
    }
}
