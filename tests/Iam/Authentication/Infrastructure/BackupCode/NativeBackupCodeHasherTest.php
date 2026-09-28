<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Infrastructure\BackupCode\NativeBackupCodeHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeBackupCodeHasherTest extends TestCase
{
    private NativeBackupCodeHasher $hasher;
    private string $hash;

    protected function setUp(): void
    {
        $this->hasher = new NativeBackupCodeHasher('secret');
        $this->hash = $this->hasher->hash('12345678');
    }

    #[Test]
    public function itHashesDeterministically(): void
    {
        // When
        $secondHash = $this->hasher->hash('12345678');

        // Then
        self::assertSame($this->hash, $secondHash);
    }

    #[Test]
    #[DataProvider('provideVerificationCases')]
    public function itVerifies(string $candidate, bool $expected): void
    {
        // When
        $verified = $this->hasher->verify($this->hash, $candidate);

        // Then
        self::assertSame($expected, $verified);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideVerificationCases(): iterable
    {
        yield 'correct code' => ['12345678', true];
        yield 'incorrect code' => ['87654321', false];
    }
}
