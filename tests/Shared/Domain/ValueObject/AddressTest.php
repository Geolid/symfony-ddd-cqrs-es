<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Address;

final class AddressTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $address = Address::of('  12 rue des Lilas  ', '  75001  ', '  Paris  ', 'FR');

        // Then
        self::assertSame('12 rue des Lilas', $address->street);
        self::assertSame('75001', $address->postalCode);
        self::assertSame('Paris', $address->city);
        self::assertSame('12 rue des Lilas, 75001 Paris, FR', $address->toString());
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
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty street' => ['', '75001', 'Paris', 'FR'];
        yield 'too long street' => [str_repeat('a', Address::STREET_MAX_LENGTH + 1), '75001', 'Paris', 'FR'];
        yield 'empty postal code' => ['12 rue des Lilas', '', 'Paris', 'FR'];
        yield 'too long postal code' => ['12 rue des Lilas', str_repeat('1', Address::POSTAL_CODE_MAX_LENGTH + 1), 'Paris', 'FR'];
        yield 'empty city' => ['12 rue des Lilas', '75001', '', 'FR'];
        yield 'too long city' => ['12 rue des Lilas', '75001', str_repeat('a', Address::CITY_MAX_LENGTH + 1), 'FR'];
        yield 'unknown country code' => ['12 rue des Lilas', '75001', 'Paris', 'XX'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Address::of('12 rue des Lilas', '75001', 'Paris', 'FR');
        $b = Address::of('  12 rue des Lilas  ', '  75001  ', '  Paris  ', 'FR');
        $differentStreet = Address::of('8 avenue Foch', '75001', 'Paris', 'FR');
        $differentPostalCode = Address::of('12 rue des Lilas', '75116', 'Paris', 'FR');
        $differentCity = Address::of('12 rue des Lilas', '75001', 'Lyon', 'FR');
        $differentCountryCode = Address::of('12 rue des Lilas', '75001', 'Paris', 'BE');

        // When
        $equalResult = $a->equals($b);
        $differentStreetResult = $a->equals($differentStreet);
        $differentPostalCodeResult = $a->equals($differentPostalCode);
        $differentCityResult = $a->equals($differentCity);
        $differentCountryCodeResult = $a->equals($differentCountryCode);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentStreetResult);
        self::assertFalse($differentPostalCodeResult);
        self::assertFalse($differentCityResult);
        self::assertFalse($differentCountryCodeResult);
    }
}
