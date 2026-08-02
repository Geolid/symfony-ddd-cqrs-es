<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Login;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $login = Login::fromString('operator');

        // Then
        self::assertSame('operator', $login->toString());
    }

    #[Test]
    public function itTrims(): void
    {
        // When
        $login = Login::fromString('  operator  ');

        // Then
        self::assertSame('operator', $login->toString());
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

    #[Test]
    public function itFingerprintsTheNormalizedValue(): void
    {
        // Given
        $login = Login::fromString('  operator  ');

        // When
        $fingerprint = $login->fingerprint();

        // Then
        self::assertSame(hash('sha256', 'operator'), $fingerprint);
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
    }
}
