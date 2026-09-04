<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\CountryCode;

final class AddressTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $street, string $postalCode, string $city, string $countryCode): void
    {
        // When
        $address = Address::of($street, $postalCode, $city, $countryCode);

        // Then
        self::assertSame($street, $address->street);
        self::assertSame($postalCode, $address->postalCode);
        self::assertSame($city, $address->city);
        self::assertSame(CountryCode::from($countryCode), $address->countryCode);
        $primitiveAddress = $address->toArray();
        self::assertSame(
            ['street' => $street, 'postalCode' => $postalCode, 'city' => $city, 'countryCode' => $countryCode],
            $primitiveAddress,
        );
    }

    /**
     * @return iterable<string, array{street: string, postalCode: string, city: string, countryCode: string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'address' => self::address();
        yield 'street at maximum length' => self::address(['street' => str_repeat('a', Address::STREET_MAX_LENGTH)]);
        yield 'postal code at maximum length' => self::address(['postalCode' => str_repeat('1', Address::POSTAL_CODE_MAX_LENGTH)]);
        yield 'city at maximum length' => self::address(['city' => str_repeat('a', Address::CITY_MAX_LENGTH)]);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $street, string $postalCode, string $city, string $countryCode): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Address::of($street, $postalCode, $city, $countryCode);
    }

    /**
     * @return iterable<string, array{street: string, postalCode: string, city: string, countryCode: string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty street' => self::address(['street' => '']);
        yield 'whitespace-only street' => self::address(['street' => '   ']);
        yield 'too long street' => self::address(['street' => str_repeat('a', Address::STREET_MAX_LENGTH + 1)]);
        yield 'empty postal code' => self::address(['postalCode' => '']);
        yield 'whitespace-only postal code' => self::address(['postalCode' => '   ']);
        yield 'too long postal code' => self::address(['postalCode' => str_repeat('1', Address::POSTAL_CODE_MAX_LENGTH + 1)]);
        yield 'empty city' => self::address(['city' => '']);
        yield 'whitespace-only city' => self::address(['city' => '   ']);
        yield 'too long city' => self::address(['city' => str_repeat('a', Address::CITY_MAX_LENGTH + 1)]);
        yield 'unknown country code' => self::address(['countryCode' => 'XX']);
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');
        $b = Address::of('  10 Rue de la Paix  ', '  75002  ', '  Paris  ', 'FR');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');

        $differentStreet = Address::of('8 Avenue Foch', '75002', 'Paris', 'FR');
        $differentPostalCode = Address::of('10 Rue de la Paix', '75016', 'Paris', 'FR');
        $differentCity = Address::of('10 Rue de la Paix', '75002', 'Lyon', 'FR');
        $differentCountryCode = Address::of('10 Rue de la Paix', '75002', 'Paris', 'BE');

        // When
        $differsOnStreet = $a->equals($differentStreet);
        $differsOnPostalCode = $a->equals($differentPostalCode);
        $differsOnCity = $a->equals($differentCity);
        $differsOnCountryCode = $a->equals($differentCountryCode);

        // Then
        self::assertFalse($differsOnStreet);
        self::assertFalse($differsOnPostalCode);
        self::assertFalse($differsOnCity);
        self::assertFalse($differsOnCountryCode);
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
}
