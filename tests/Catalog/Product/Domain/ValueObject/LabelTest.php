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
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value, string $expected): void
    {
        // When
        $label = Label::fromString($value);

        // Then
        self::assertSame($expected, $label->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'label' => ['Espresso cups, set of 6', 'Espresso cups, set of 6'];
        yield 'maximum length' => [str_repeat('a', 255), str_repeat('a', 255)];
        yield 'surrounding whitespace' => ['  Espresso cups, set of 6  ', 'Espresso cups, set of 6'];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Label::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('a', 256)];
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
}
