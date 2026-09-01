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
        $this->validateValue(self::address());

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
        yield 'empty street' => [self::address(['street' => ''])];
        yield 'whitespace only street' => [self::address(['street' => '   '])];
        yield 'empty postal code' => [self::address(['postalCode' => ''])];
        yield 'empty city' => [self::address(['city' => ''])];
        yield 'street too long' => [self::address(['street' => str_repeat('a', Address::STREET_MAX_LENGTH + 1)])];
        yield 'postal code too long' => [self::address(['postalCode' => str_repeat('1', Address::POSTAL_CODE_MAX_LENGTH + 1)])];
        yield 'city too long' => [self::address(['city' => str_repeat('a', Address::CITY_MAX_LENGTH + 1)])];
        yield 'unknown country code' => [self::address(['countryCode' => 'XX'])];
    }

    #[Test]
    public function itRefusesMultipleFields(): void
    {
        // Given
        $value = self::address(['street' => '', 'city' => str_repeat('a', Address::CITY_MAX_LENGTH + 1)]);

        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(2);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    #[Test]
    public function itRefusesWhenFieldMissing(): void
    {
        // Given
        $address = self::address();
        unset($address['city']);

        // When
        $this->validateValue($address);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    protected function createCompound(): ValidAddress
    {
        return new ValidAddress();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array{street: string, postalCode: string, city: string, countryCode: string}
     */
    private static function address(array $overrides = []): array
    {
        return $overrides + [
                'street' => '10 Rue de la Paix',
                'postalCode' => '75002',
                'city' => 'Paris',
                'countryCode' => 'FR',
            ];
    }

    private function collection(): Assert\Collection
    {
        $normalizer = 'trim';

        return new Assert\Collection(
            fields: [
                'street' => [
                    new Assert\NotBlank(normalizer: $normalizer),
                    new Assert\Length(max: Address::STREET_MAX_LENGTH),
                ],
                'postalCode' => [
                    new Assert\NotBlank(normalizer: $normalizer),
                    new Assert\Length(max: Address::POSTAL_CODE_MAX_LENGTH),
                ],
                'city' => [
                    new Assert\NotBlank(normalizer: $normalizer),
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
