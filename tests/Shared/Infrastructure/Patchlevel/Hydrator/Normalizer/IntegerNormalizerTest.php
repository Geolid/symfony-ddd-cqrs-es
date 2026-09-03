<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;

final class IntegerNormalizerTest extends TestCase
{
    private IntegerNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new IntegerNormalizer();
    }

    #[Test]
    #[DataProvider('provideNormalizableValues')]
    public function itNormalizes(?int $value): void
    {
        // When
        $normalized = $this->normalizer->normalize($value);

        // Then
        self::assertSame($value, $normalized);
    }

    /**
     * @return iterable<string, array{?int}>
     */
    public static function provideNormalizableValues(): iterable
    {
        yield 'int' => [42];
        yield 'null' => [null];
    }

    #[Test]
    public function itThrowsWhenNormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->normalize('42');
    }

    #[Test]
    #[DataProvider('provideDenormalizableValues')]
    public function itDenormalizes(mixed $value, ?int $expected): void
    {
        // When
        $denormalized = $this->normalizer->denormalize($value);

        // Then
        self::assertSame($expected, $denormalized);
    }

    /**
     * @return iterable<string, array{mixed, ?int}>
     */
    public static function provideDenormalizableValues(): iterable
    {
        yield 'int' => [42, 42];
        yield 'zero' => [0, 0];
        yield 'numeric string' => ['42', 42];
        yield 'negative numeric string' => ['-5', -5];
        yield 'zero string' => ['0', 0];
        yield 'null' => [null, null];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itThrowsWhenDenormalizingWrongType(mixed $value): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->denormalize($value);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'non numeric string' => ['abc'];
        yield 'float' => [4.2];
        yield 'leading zero string' => ['007'];
    }
}
