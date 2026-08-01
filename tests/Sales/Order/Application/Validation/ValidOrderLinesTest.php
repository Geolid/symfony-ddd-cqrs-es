<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Validation\ValidOrderLines;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

final class ValidOrderLinesTest extends CompoundConstraintTestCase
{
    public function createCompound(): Compound
    {
        return new ValidOrderLines();
    }

    #[Test]
    public function itAcceptsASingleWellShapedLine(): void
    {
        // When
        $this->validateValue([self::line()]);

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedLines')]
    public function itRefusesLines(mixed $lines, string $code): void
    {
        // When
        $this->validateValue($lines);

        // Then
        $this->assertViolationIsRaisedByCompound($code);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideRefusedLines(): iterable
    {
        yield 'no line at all' => [[], Assert\Count::TOO_FEW_ERROR];
        yield 'a countable that is not an array' => [new \ArrayObject([self::line()]), Assert\Type::INVALID_TYPE_ERROR];
        yield 'a label that is not a string' => [[[...self::line(), 'label' => 1]], Assert\Type::INVALID_TYPE_ERROR];
        yield 'a quantity that is not a whole number' => [[[...self::line(), 'quantity' => '2']], Assert\Type::INVALID_TYPE_ERROR];
        yield 'an amount that is not a whole number' => [[[...self::line(), 'unitAmountInCents' => 19.99]], Assert\Type::INVALID_TYPE_ERROR];
        yield 'a field the line does not carry' => [[[...self::line(), 'discount' => 10]], Assert\Collection::NO_SUCH_FIELD_ERROR];
    }

    /**
     * @return array{label: string, quantity: int, unitAmountInCents: int}
     */
    private static function line(): array
    {
        return ['label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750];
    }
}
