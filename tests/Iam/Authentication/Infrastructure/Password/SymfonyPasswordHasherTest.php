<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Infrastructure\Password\SymfonyPasswordHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class SymfonyPasswordHasherTest extends TestCase
{
    private SymfonyPasswordHasher $hasher;
    private string $hash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new SymfonyPasswordHasher(new NativePasswordHasher());
        $this->hash = $this->hasher->hash('Marmoset-42-Zephyr!');
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
        yield 'correct password' => ['Marmoset-42-Zephyr!', true];
        yield 'incorrect password' => ['WrongHorse456!', false];
        yield 'empty password' => ['', false];
    }

    #[Test]
    public function itRequiresRehash(): void
    {
        // Given
        $lowCostHasher = new SymfonyPasswordHasher(new NativePasswordHasher(cost: 4));
        $highCostHasher = new SymfonyPasswordHasher(new NativePasswordHasher(cost: 12));

        // When
        $hash = $lowCostHasher->hash('Marmoset-42-Zephyr!');
        $needsRehash = $highCostHasher->needsRehash($hash);

        // Then
        self::assertTrue($needsRehash);
    }
}
