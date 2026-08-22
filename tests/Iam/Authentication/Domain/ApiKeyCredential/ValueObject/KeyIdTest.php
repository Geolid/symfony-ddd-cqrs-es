<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential\ValueObject;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KeyIdTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $keyId = KeyId::fromString(KeyId::PREFIX.'0123456789abcdef');

        // Then
        self::assertSame(KeyId::PREFIX.'0123456789abcdef', $keyId->value);
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
        yield 'too short' => [KeyId::PREFIX.'0123456789abcde'];
        yield 'too long' => [KeyId::PREFIX.'0123456789abcdef0'];
        yield 'missing key_ prefix' => ['abc_0123456789abcdef'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // When
        $a = KeyId::fromString(KeyId::PREFIX.'0123456789abcdef');
        $b = KeyId::fromString(KeyId::PREFIX.'0123456789abcdef');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(KeyId::fromString(KeyId::PREFIX.'fedcba9876543210')));
    }
}
