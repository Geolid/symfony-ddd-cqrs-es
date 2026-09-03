<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\ApiKey;

use Iam\Authentication\Infrastructure\ApiKey\NativeApiKeyHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeApiKeyHasherTest extends TestCase
{
    private NativeApiKeyHasher $hasher;
    private string $hash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new NativeApiKeyHasher();
        $this->hash = $this->hasher->hash('a-secret-value');
    }

    #[Test]
    public function itHashesDeterministically(): void
    {
        // When
        $secondHash = $this->hasher->hash('a-secret-value');

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
        yield 'correct secret' => ['a-secret-value', true];
        yield 'incorrect secret' => ['another-secret-value', false];
    }
}
