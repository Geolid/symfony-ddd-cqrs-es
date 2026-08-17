<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\Login;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value, string $expected): void
    {
        // When
        $login = Login::fromString($value);

        // Then
        self::assertSame($expected, $login->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'login' => ['operator', 'operator'];
        yield 'maximum length' => [str_pad('operator', 50, 'operator'), str_pad('operator', 50, 'operator')];
        yield 'surrounding whitespace' => ['  operator  ', 'operator'];
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
        yield 'too long' => [str_pad('operator', 51, 'operator')];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Login::fromString('operator');
        $b = Login::fromString('  operator  ');
        $other = Login::fromString('another-operator');

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
    }
}
