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
    #[DataProvider('provideDigitCounts')]
    public function itGenerates(int $digitCount): void
    {
        // When
        $code = $this->generator->generate($digitCount);

        // Then
        self::assertMatchesRegularExpression(\sprintf('/^\d{%d}$/', $digitCount), $code);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideDigitCounts(): iterable
    {
        yield 'single digit' => [1];
        yield 'typical digit count' => [8];
        yield 'long digit count' => [40];
    }

    #[Test]
    #[DataProvider('provideInvalidDigitCount')]
    public function itThrowsWithInvalidDigitCount(int $digitCount): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->generator->generate($digitCount);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideInvalidDigitCount(): iterable
    {
        yield 'zero digit count' => [0];
        yield 'negative digit count' => [-1];
    }
}
