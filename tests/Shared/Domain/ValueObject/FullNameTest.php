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
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $firstName, string $lastName): void
    {
        // When
        $fullName = FullName::of($firstName, $lastName);

        // Then
        self::assertSame($firstName, $fullName->firstName);
        self::assertSame($lastName, $fullName->lastName);
        self::assertSame(\sprintf('%s %s', $firstName, $lastName), $fullName->toString());
    }

    /**
     * @return iterable<string, array{firstName: string, lastName: string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'full name' => self::fullName();
        yield 'first name at maximum length' => self::fullName(['firstName' => str_repeat('a', FullName::MAX_LENGTH)]);
        yield 'last name at maximum length' => self::fullName(['lastName' => str_repeat('a', FullName::MAX_LENGTH)]);
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
     * @return iterable<string, array{firstName: string, lastName: string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty first name' => self::fullName(['firstName' => '']);
        yield 'whitespace-only first name' => self::fullName(['firstName' => '   ']);
        yield 'too long first name' => self::fullName(['firstName' => str_repeat('a', FullName::MAX_LENGTH + 1)]);
        yield 'empty last name' => self::fullName(['lastName' => '']);
        yield 'whitespace-only last name' => self::fullName(['lastName' => '   ']);
        yield 'too long last name' => self::fullName(['lastName' => str_repeat('a', FullName::MAX_LENGTH + 1)]);
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = FullName::of('John', 'Doe');
        $b = FullName::of('  John  ', '  Doe  ');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = FullName::of('John', 'Doe');
        $differentFirstName = FullName::of('Jane', 'Doe');
        $differentLastName = FullName::of('John', 'Smith');

        // When
        $differsOnFirstName = $a->equals($differentFirstName);
        $differsOnLastName = $a->equals($differentLastName);

        // Then
        self::assertFalse($differsOnFirstName);
        self::assertFalse($differsOnLastName);
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array{firstName: string, lastName: string}
     */
    private static function fullName(array $overrides = []): array
    {
        return $overrides + [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];
    }
}
