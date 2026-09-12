<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Domain\Customer\ValueObject;

use Crm\Customer\Domain\Customer\ValueObject\Name;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NameTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        // When
        $name = Name::fromString($value);

        // Then
        self::assertSame($value, $name->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'name' => ['Jean'];
        yield 'maximum length' => [str_repeat('a', Name::MAX_LENGTH)];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Name::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('a', Name::MAX_LENGTH + 1)];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Name::fromString('Jean');
        $b = Name::fromString('  Jean  ');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Name::fromString('Jean');
        $b = Name::fromString('Marie');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
