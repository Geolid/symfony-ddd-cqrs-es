<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Infrastructure\BackupCode\NativeBackupCodeGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Random\NativeNumericCodeGenerator;

final class NativeBackupCodeGeneratorTest extends TestCase
{
    private NativeBackupCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NativeBackupCodeGenerator(new NativeNumericCodeGenerator(), 8);
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
