<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Domain\ValueObject;

use Catalog\Product\Domain\ValueObject\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $label = Label::fromString('Espresso cups, set of 6');

        // Then
        self::assertSame('Espresso cups, set of 6', $label->toString());
    }

    #[Test]
    public function itAcceptsTheMaximumLength(): void
    {
        // When
        $label = Label::fromString(str_repeat('a', 255));

        // Then
        self::assertSame(str_repeat('a', 255), $label->toString());
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $label = Label::fromString('  Espresso cups, set of 6  ');

        // Then
        self::assertSame('Espresso cups, set of 6', $label->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Label::fromString('Espresso cups, set of 6');
        $b = Label::fromString('  Espresso cups, set of 6  ');
        $other = Label::fromString('Wireless mouse');

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value, string $reason): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($reason);

        // When
        Label::fromString($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => ['', '/cannot be empty/'];
        yield 'whitespace only' => ['   ', '/cannot be empty/'];
        yield 'too long' => [str_repeat('a', 256), '/cannot exceed 255 characters/'];
    }
}
