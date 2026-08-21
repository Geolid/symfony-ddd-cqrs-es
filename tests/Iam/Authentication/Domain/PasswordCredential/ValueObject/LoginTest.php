<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential\ValueObject;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $login = Login::fromString('ada.lovelace');

        // Then
        self::assertSame('ada.lovelace', $login->value);
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $login = Login::fromString('  ada.lovelace  ');

        // Then
        self::assertSame('ada.lovelace', $login->value);
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // When
        $a = Login::fromString('ada.lovelace');
        $b = Login::fromString('ada.lovelace');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(Login::fromString('grace.hopper')));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Login::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('a', 51)];
    }
}
