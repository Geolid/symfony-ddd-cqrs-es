<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Validation\ValidOrderLines;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidOrderLines>
 */
final class ValidOrderLinesTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsASingleWellShapedLine(): void
    {
        // When
        $this->validateValue([self::line()]);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedLines')]
    public function itRefusesLines(mixed $lines, array $rules): void
    {
        // When
        $this->validateValue($lines);

        // Then
        self::assertGreaterThan(0, \count($this->context->getViolations()));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedLines(): iterable
    {
        yield 'no line at all' => [[], [new Assert\Count(min: 1)]];
        yield 'a countable that is not an array' => [new \ArrayObject([self::line()]), [new Assert\Type('array')]];
        yield 'a productId that is not a string' => [[[...self::line(), 'productId' => 1]], [self::lineShape()]];
        yield 'a blank productId' => [[[...self::line(), 'productId' => '   ']], [self::lineShape()]];
        yield 'a quantity that is not a whole number' => [[[...self::line(), 'quantity' => '2']], [self::lineShape()]];
        yield 'a quantity that is not positive' => [[[...self::line(), 'quantity' => 0]], [self::lineShape()]];
        yield 'a field the line does not carry' => [[[...self::line(), 'discount' => 10]], [self::lineShape()]];
    }

    protected function createCompound(): ValidOrderLines
    {
        return new ValidOrderLines();
    }

    private static function lineShape(): Assert\All
    {
        return new Assert\All([
            new Assert\Collection([
                'productId' => [new Assert\Type('string'), new Assert\NotBlank(normalizer: 'trim')],
                'quantity' => [new Assert\Type('int'), new Assert\Positive()],
            ]),
        ]);
    }

    /**
     * @return array{productId: string, quantity: int}
     */
    private static function line(): array
    {
        return ['productId' => '0193c5f4-1a44-7d18-b2c7-6f9e0a4d8b31', 'quantity' => 1];
    }
}
