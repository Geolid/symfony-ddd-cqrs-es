<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Validation\ValidMoney;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

final class ValidMoneyTest extends CompoundConstraintTestCase
{
    public function createCompound(): Compound
    {
        return new ValidMoney();
    }

    #[Test]
    public function itAcceptsAnAmountInCents(): void
    {
        // When
        $this->validateValue(2_500);

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedAmounts')]
    public function itRefusesAnAmount(mixed $amount, string $code): void
    {
        // When
        $this->validateValue($amount);

        // Then
        $this->assertViolationIsRaisedByCompound($code);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideRefusedAmounts(): iterable
    {
        yield 'not a whole number' => [19.99, Assert\Type::INVALID_TYPE_ERROR];
        yield 'negative' => [-1, Assert\PositiveOrZero::TOO_LOW_ERROR];
        yield 'refused by the value object' => [-1, ValidValueObject::DOMAIN_VALIDATION_ERROR];
    }
}
