<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $value, string $expected): void
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
        yield 'a label' => ['CI pipeline', 'CI pipeline'];
        yield 'the maximum length' => [str_pad('CI pipeline', 255, 'x'), str_pad('CI pipeline', 255, 'x')];
        yield 'surrounding whitespace' => ['  CI pipeline  ', 'CI pipeline'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Label::fromString('CI pipeline');
        $b = Label::fromString('  CI pipeline  ');
        $other = Label::fromString('mobile app');

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
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
        yield 'too long' => [str_pad('CI pipeline', 256, 'x')];
    }
}
