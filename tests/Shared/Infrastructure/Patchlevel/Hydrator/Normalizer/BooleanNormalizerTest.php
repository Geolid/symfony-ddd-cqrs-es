<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;

final class BooleanNormalizerTest extends TestCase
{
    private BooleanNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BooleanNormalizer();
    }

    #[Test]
    #[DataProvider('provideNormalizableValues')]
    public function itNormalizes(?bool $value): void
    {
        // When
        $normalized = $this->normalizer->normalize($value);

        // Then
        self::assertSame($value, $normalized);
    }

    /**
     * @return iterable<string, array{?bool}>
     */
    public static function provideNormalizableValues(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
    }

    #[Test]
    public function itThrowsWhenNormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->normalize('true');
    }

    #[Test]
    #[DataProvider('provideDenormalizableValues')]
    public function itDenormalizes(mixed $value, ?bool $expected): void
    {
        // When
        $denormalized = $this->normalizer->denormalize($value);

        // Then
        self::assertSame($expected, $denormalized);
    }

    /**
     * @return iterable<string, array{mixed, ?bool}>
     */
    public static function provideDenormalizableValues(): iterable
    {
        yield 'true' => [true, true];
        yield 'one int' => [1, true];
        yield 'one string' => ['1', true];
        yield 'true string' => ['true', true];
        yield 'false' => [false, false];
        yield 'zero int' => [0, false];
        yield 'zero string' => ['0', false];
        yield 'false string' => ['false', false];
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
        yield 'unrecognized string' => ['yes'];
        yield 'different case' => ['TRUE'];
        yield 'unrecognized int' => [2];
    }
}
