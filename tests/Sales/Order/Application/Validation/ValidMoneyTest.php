<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Validation\ValidMoney;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ValidMoneyTest extends TestCase
{
    #[Test]
    public function itAcceptsAnAmountInCents(): void
    {
        // Then
        self::assertCount(0, self::validate(2_500));
        self::assertCount(0, self::validate(0));
    }

    #[Test]
    #[DataProvider('provideRefusedAmounts')]
    public function itRefusesAnAmount(mixed $amount, int $violations): void
    {
        // Then
        self::assertCount($violations, self::validate($amount));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function provideRefusedAmounts(): iterable
    {
        yield 'a value that is not a whole number' => [19.99, 2];
        yield 'a negative amount' => [-1, 2];
    }

    private static function validate(mixed $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new ValidMoney());
    }
}
