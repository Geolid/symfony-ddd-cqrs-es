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
        $password = Password::fromString('Marmoset-42-Zephyr!');

        // Then
        self::assertSame('Marmoset-42-Zephyr!', $password->value);
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

    #[Test]
    public function itComparesEquality(): void
    {
        // When
        $a = Password::fromString('Marmoset-42-Zephyr!');
        $b = Password::fromString('Marmoset-42-Zephyr!');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(Password::fromString('AnotherHorse456!')));
    }
}
