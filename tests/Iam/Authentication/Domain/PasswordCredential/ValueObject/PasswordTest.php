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
    public function itCreates(): void
    {
        // When
        $password = Password::fromString('Xk9$mQ2vLp7&zR4w');

        // Then
        self::assertSame('Xk9$mQ2vLp7&zR4w', $password->value);
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // When
        $a = Password::fromString('Xk9$mQ2vLp7&zR4w');
        $b = Password::fromString('Xk9$mQ2vLp7&zR4w');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(Password::fromString('AnotherHorse456!')));
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
        yield 'too short' => ['tooshort1!'];
        yield 'too long' => [str_repeat('a', 4_097)];
    }
}
