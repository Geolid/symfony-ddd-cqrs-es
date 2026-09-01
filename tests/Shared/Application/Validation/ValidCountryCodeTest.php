<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidCountryCode;
use Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidCountryCode>
 */
final class ValidCountryCodeTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('FR');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(string $countryCode, Assert\NotBlank|Assert\Choice $rule): void
    {
        // When
        $this->validateValue($countryCode);

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
        yield 'unknown country code' => ['XX', self::countryCodeChoice()];
        yield 'lowercase' => ['fr', self::countryCodeChoice()];
    }

    protected function createCompound(): ValidCountryCode
    {
        return new ValidCountryCode();
    }

    private static function countryCodeChoice(): Assert\Choice
    {
        return new Assert\Choice(callback: CountryCode::values(...));
    }
}
