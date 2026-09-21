<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Totp;

use Iam\Authentication\Infrastructure\Totp\NativeTotpBackupCodeGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Random\NativeNumericCodeGenerator;

final class NativeTotpBackupCodeGeneratorTest extends TestCase
{
    private NativeTotpBackupCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NativeTotpBackupCodeGenerator(new NativeNumericCodeGenerator());
    }

    #[Test]
    public function itGenerates(): void
    {
        // When
        $codes = $this->generator->generate(5);

        // Then
        self::assertCount(5, $codes);
        self::assertCount(5, array_unique($codes));

        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^\d{8}$/', $code);
        }
    }
}
