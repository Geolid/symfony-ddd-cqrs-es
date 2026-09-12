<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Domain\Customer\ValueObject;

use Crm\Customer\Domain\Customer\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $email = Email::fromString('jean.dupont@example.com');

        // Then
        self::assertSame('jean.dupont@example.com', $email->value);
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
        yield 'missing domain' => ['jean.dupont@'];
        yield 'missing local part' => ['@example.com'];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Email::fromString('jean.dupont@example.com');
        $b = Email::fromString('  Jean.Dupont@Example.COM  ');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Email::fromString('jean.dupont@example.com');
        $b = Email::fromString('other@example.com');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
