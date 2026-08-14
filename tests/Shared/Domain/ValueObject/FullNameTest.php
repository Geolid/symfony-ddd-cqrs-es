<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\FullName;

final class FullNameTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $fullName = FullName::of('  Ada  ', '  Lovelace  ');

        // Then
        self::assertSame('Ada', $fullName->firstName);
        self::assertSame('Lovelace', $fullName->lastName);
        self::assertSame('Ada Lovelace', $fullName->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $firstName, string $lastName): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        FullName::of($firstName, $lastName);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty first name' => ['', 'Lovelace'];
        yield 'whitespace-only first name' => ['   ', 'Lovelace'];
        yield 'too long first name' => [str_repeat('a', 256), 'Lovelace'];
        yield 'empty last name' => ['Ada', ''];
        yield 'whitespace-only last name' => ['Ada', '   '];
        yield 'too long last name' => ['Ada', str_repeat('a', 256)];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = FullName::of('Ada', 'Lovelace');
        $b = FullName::of('  Ada  ', '  Lovelace  ');
        $differentFirstName = FullName::of('Grace', 'Lovelace');
        $differentLastName = FullName::of('Ada', 'Hopper');

        // When
        $equalResult = $a->equals($b);
        $differentFirstNameResult = $a->equals($differentFirstName);
        $differentLastNameResult = $a->equals($differentLastName);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentFirstNameResult);
        self::assertFalse($differentLastNameResult);
    }
}
