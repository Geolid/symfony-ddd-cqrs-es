<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidAddress;
use Shared\Application\Validation\ValidCountryCode;
use Shared\Domain\ValueObject\Address;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidAddress>
 */
final class ValidAddressTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param array{street: string, postalCode: string, city: string, countryCode: string} $value
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
     * @return iterable<string, array{array{street: string, postalCode: string, city: string, countryCode: string}}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty street' => [['street' => '', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']];
        yield 'whitespace only street' => [['street' => '   ', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']];
        yield 'empty postal code' => [['street' => '12 rue des Lilas', 'postalCode' => '', 'city' => 'Paris', 'countryCode' => 'FR']];
        yield 'empty city' => [['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => '', 'countryCode' => 'FR']];
        yield 'street too long' => [['street' => str_repeat('a', Address::STREET_MAX_LENGTH + 1), 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']];
        yield 'postal code too long' => [['street' => '12 rue des Lilas', 'postalCode' => str_repeat('1', Address::POSTAL_CODE_MAX_LENGTH + 1), 'city' => 'Paris', 'countryCode' => 'FR']];
        yield 'city too long' => [['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => str_repeat('a', Address::CITY_MAX_LENGTH + 1), 'countryCode' => 'FR']];
        yield 'unknown country code' => [['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'XX']];
    }

    #[Test]
    public function itDoesNotStopAtTheFirstInvalidField(): void
    {
        // When
        $this->validateValue(['street' => '', 'postalCode' => '75001', 'city' => str_repeat('a', Address::CITY_MAX_LENGTH + 1), 'countryCode' => 'FR']);

        // Then
        $this->assertViolationsCount(2);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    #[Test]
    public function itRefusesWhenAFieldIsMissing(): void
    {
        // When
        $this->validateValue(['street' => '12 rue des Lilas', 'postalCode' => '75001', 'countryCode' => 'FR']);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    protected function createCompound(): ValidAddress
    {
        return new ValidAddress();
    }

    private function collection(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'street' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: Address::STREET_MAX_LENGTH),
                ],
                'postalCode' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: Address::POSTAL_CODE_MAX_LENGTH),
                ],
                'city' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: Address::CITY_MAX_LENGTH),
                ],
                'countryCode' => [
                    new ValidCountryCode(),
                ],
            ],
            allowMissingFields: false,
        );
    }
}
