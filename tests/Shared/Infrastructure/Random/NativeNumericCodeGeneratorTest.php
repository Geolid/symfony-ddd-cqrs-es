<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Random;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Random\NativeNumericCodeGenerator;

final class NativeNumericCodeGeneratorTest extends TestCase
{
    private NativeNumericCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NativeNumericCodeGenerator();
    }

    #[Test]
    public function itGenerates(): void
    {
        // Given
        $digitCount = 8;

        // When
        $code = $this->generator->generate($digitCount);

        // Then
        self::assertMatchesRegularExpression(\sprintf('/^\d{%d}$/', $digitCount), $code);
    }

    #[Test]
    #[DataProvider('provideDigitCountsAtBounds')]
    public function itGeneratesAtBounds(int $digitCount): void
    {
        // When
        $code = $this->generator->generate($digitCount);

        // Then
        self::assertMatchesRegularExpression(\sprintf('/^\d{%d}$/', $digitCount), $code);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideDigitCountsAtBounds(): iterable
    {
        yield 'minimum' => [1];
        yield 'maximum' => [18];
    }

    #[Test]
    #[DataProvider('provideDigitCountsOutOfBounds')]
    public function itFailsWhenDigitCountOutOfBounds(int $digitCount): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->generator->generate($digitCount);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideDigitCountsOutOfBounds(): iterable
    {
        yield 'zero' => [0];
        yield 'above maximum' => [19];
    }
}
