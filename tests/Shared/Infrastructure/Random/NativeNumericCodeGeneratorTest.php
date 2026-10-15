<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Random;

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
}
