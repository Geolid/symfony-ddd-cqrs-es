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
        $login = Login::fromString('operator');

        self::assertSame('operator', $login->toString());
    }

    #[Test]
    public function itTrims(): void
    {
        $login = Login::fromString('  operator  ');

        self::assertSame('operator', $login->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $a = Login::fromString('operator');
        $b = Login::fromString('  operator  ');
        $other = Login::fromString('another-operator');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }

    #[Test]
    public function itFingerprintsTheNormalizedValue(): void
    {
        $login = Login::fromString('  operator  ');

        self::assertSame(hash('sha256', 'operator'), $login->fingerprint());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

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
