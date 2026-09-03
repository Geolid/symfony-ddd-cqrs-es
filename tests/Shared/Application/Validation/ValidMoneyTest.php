<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidMoney>
 */
final class ValidMoneyTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(int $amount): void
    {
        // When
        $this->validateValue($amount);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'amount' => [2_500];
        yield 'zero' => [0];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $amount, array $rules): void
    {
        // When
        $this->validateValue($amount);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'missing' => [null, [new Assert\NotNull()]];
        yield 'not a whole number' => [19.99, [new Assert\Type('int')]];
        yield 'negative' => [-1, [new Assert\PositiveOrZero()]];
    }

    protected function createCompound(): ValidMoney
    {
        return new ValidMoney();
    }
}
