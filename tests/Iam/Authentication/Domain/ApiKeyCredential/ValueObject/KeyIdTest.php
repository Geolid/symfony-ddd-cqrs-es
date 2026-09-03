<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential\ValueObject;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KeyIdTest extends TestCase
{
    private const string VALID_KEY = 'key_0123456789abcdef';

    #[Test]
    public function itCreates(): void
    {
        // When
        $keyId = KeyId::fromString(self::VALID_KEY);

        // Then
        self::assertSame(self::VALID_KEY, $keyId->value);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        KeyId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'too short' => [KeyId::PREFIX.str_repeat('a', self::suffixLength() - 1)];
        yield 'too long' => [KeyId::PREFIX.str_repeat('a', self::suffixLength() + 1)];
        yield 'invalid prefix' => ['abc_'.str_repeat('a', self::suffixLength())];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = KeyId::fromString(self::VALID_KEY);
        $b = KeyId::fromString(self::VALID_KEY);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = KeyId::fromString(self::VALID_KEY);
        $b = KeyId::fromString('key_fedcba9876543210');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }

    private static function suffixLength(): int
    {
        return KeyId::LENGTH - \strlen(KeyId::PREFIX);
    }
}
