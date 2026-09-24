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
        // When
        $code = $this->generator->generate(8);

        // Then
        self::assertMatchesRegularExpression('/^\d{8}$/', $code);
    }
}
