<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Customer\Domain\ValueObject\Email;

final class EmailTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $email = Email::fromString('buyer@example.com');

        // Then
        self::assertSame('buyer@example.com', $email->toString());
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $email = Email::fromString('  Buyer@Example.COM  ');

        // Then
        self::assertSame('buyer@example.com', $email->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Email::fromString('buyer@example.com');
        $b = Email::fromString('  Buyer@Example.COM  ');
        $other = Email::fromString('other@example.com');

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
        $email = Email::fromString('  Buyer@Example.COM  ');

        // When
        $fingerprint = $email->fingerprint();

        // Then
        self::assertSame(hash('sha256', 'buyer@example.com'), $fingerprint);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value, string $reason): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($reason);

        // When
        Email::fromString($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => ['', '/cannot be empty/'];
        yield 'whitespace only' => ['   ', '/cannot be empty/'];
        yield 'missing at sign' => ['not-an-address', '/is expected/'];
        yield 'missing domain' => ['buyer@', '/is expected/'];
        yield 'missing local part' => ['@example.com', '/is expected/'];
    }
}
