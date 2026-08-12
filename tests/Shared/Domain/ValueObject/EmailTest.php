<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Email;

final class EmailTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value, string $expected): void
    {
        // When
        $email = Email::fromString($value);

        // Then
        self::assertSame($expected, $email->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'email' => ['buyer@example.com', 'buyer@example.com'];
        yield 'mixed case and surrounding whitespace' => ['  Buyer@Example.COM  ', 'buyer@example.com'];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Email::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'missing at sign' => ['not-an-address'];
        yield 'missing domain' => ['buyer@'];
        yield 'missing local part' => ['@example.com'];
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
    public function itFingerprints(): void
    {
        // Given
        $email = Email::fromString('  Buyer@Example.COM  ');

        // When
        $fingerprint = $email->fingerprint();

        // Then
        self::assertSame(hash('sha256', 'buyer@example.com'), $fingerprint);
    }
}
