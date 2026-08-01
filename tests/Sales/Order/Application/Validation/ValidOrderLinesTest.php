<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Validation\ValidOrderLines;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ValidOrderLinesTest extends TestCase
{
    #[Test]
    public function itAcceptsASingleWellShapedLine(): void
    {
        // Then
        self::assertCount(0, self::validate([self::line()]));
    }

    #[Test]
    #[DataProvider('provideRefusedLines')]
    public function itRefusesLines(mixed $lines, int $violations): void
    {
        // Then
        self::assertCount($violations, self::validate($lines));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function provideRefusedLines(): iterable
    {
        yield 'no line at all' => [[], 1];
        yield 'a countable that is not an array' => [new \ArrayObject([self::line()]), 1];
        yield 'a label that is not a string' => [[[...self::line(), 'label' => 1]], 1];
        yield 'a quantity that is not a whole number' => [[[...self::line(), 'quantity' => '2']], 1];
        yield 'an amount that is not a whole number' => [[[...self::line(), 'unitAmountInCents' => 19.99]], 1];
        yield 'a field the line does not carry' => [[[...self::line(), 'discount' => 10]], 1];
    }

    /**
     * @return array{label: string, quantity: int, unitAmountInCents: int}
     */
    private static function line(): array
    {
        return ['label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750];
    }

    private static function validate(mixed $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new ValidOrderLines());
    }
}
