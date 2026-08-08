<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidMoney;
use Shared\Application\Validation\ValidValueObject;
use Shared\Domain\ValueObject\Money;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidMoney>
 */
final class ValidMoneyTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsAnAmountInCents(): void
    {
        // When
        $this->validateValue(2_500);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedAmounts')]
    public function itRefusesAnAmount(mixed $amount, array $rules): void
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
    public static function provideRefusedAmounts(): iterable
    {
        yield 'missing' => [null, [new Assert\NotNull()]];
        yield 'not a whole number' => [19.99, [new Assert\Type('int'), self::valueObject()]];
        yield 'negative' => [-1, [new Assert\PositiveOrZero(), self::valueObject()]];
    }

    protected function createCompound(): ValidMoney
    {
        return new ValidMoney();
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Money::class, method: 'fromCents');
    }
}
