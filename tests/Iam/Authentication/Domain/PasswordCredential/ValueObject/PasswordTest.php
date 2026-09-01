<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential\ValueObject;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        // When
        $password = Password::fromString($value);

        // Then
        self::assertSame($value, $password->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'password' => ['P@ssword12345678'];
        yield 'minimum length' => [str_repeat('a', Password::MIN_LENGTH)];
        yield 'maximum length' => [str_repeat('a', Password::MAX_LENGTH)];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Password::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'too short' => [str_repeat('a', Password::MIN_LENGTH - 1)];
        yield 'too long' => [str_repeat('a', Password::MAX_LENGTH + 1)];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Password::fromString('P@ssword12345678');
        $b = Password::fromString('P@ssword12345678');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Password::fromString('P@ssword12345678');
        $b = Password::fromString('OtherP@ssword12345678');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
