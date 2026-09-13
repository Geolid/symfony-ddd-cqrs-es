<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidCurrency;
use Shared\Domain\ValueObject\Currency;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidCurrency>
 */
final class ValidCurrencyTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('EUR');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(string $currency, Assert\NotBlank|Assert\Choice $rule): void
    {
        // When
        $this->validateValue($currency);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$rule]);
    }

    /**
     * @return iterable<string, array{string, Assert\NotBlank|Assert\Choice}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', new Assert\NotBlank()];
        yield 'unknown currency' => ['XXX', new Assert\Choice(callback: Currency::values(...))];
        yield 'lowercase' => ['eur', new Assert\Choice(callback: Currency::values(...))];
    }

    protected function createCompound(): ValidCurrency
    {
        return new ValidCurrency();
    }
}
