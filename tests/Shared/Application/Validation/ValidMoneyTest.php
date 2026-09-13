<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidCurrency;
use Shared\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidMoney>
 */
final class ValidMoneyTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(self::money());

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param array<string, mixed> $value
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(array $value): void
    {
        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'missing cents' => [self::money(['cents' => null])];
        yield 'not a whole number' => [self::money(['cents' => 19.99])];
        yield 'negative' => [self::money(['cents' => -1])];
        yield 'unknown currency' => [self::money(['currency' => 'XXX'])];
    }

    #[Test]
    public function itRefusesWhenFieldMissing(): void
    {
        // Given
        $money = self::money();
        unset($money['currency']);

        // When
        $this->validateValue($money);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    protected function createCompound(): ValidMoney
    {
        return new ValidMoney();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function money(array $overrides = []): array
    {
        return $overrides + [
            'cents' => 2_500,
            'currency' => 'EUR',
        ];
    }

    private function collection(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'cents' => [
                    new Assert\NotNull(),
                    new Assert\Type('int'),
                    new Assert\PositiveOrZero(),
                ],
                'currency' => [
                    new ValidCurrency(),
                ],
            ],
            allowMissingFields: false,
        );
    }
}
