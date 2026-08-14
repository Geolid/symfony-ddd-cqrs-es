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
        $address = Address::of('  12 rue des Lilas  ', '  75001  ', '  Paris  ');

        // Then
        self::assertSame('12 rue des Lilas', $address->street);
        self::assertSame('75001', $address->postalCode);
        self::assertSame('Paris', $address->city);
        self::assertSame('12 rue des Lilas, 75001 Paris', $address->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $street, string $postalCode, string $city): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Address::of($street, $postalCode, $city);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty street' => ['', '75001', 'Paris'];
        yield 'too long street' => [str_repeat('a', 256), '75001', 'Paris'];
        yield 'empty postal code' => ['12 rue des Lilas', '', 'Paris'];
        yield 'too long postal code' => ['12 rue des Lilas', str_repeat('1', 21), 'Paris'];
        yield 'empty city' => ['12 rue des Lilas', '75001', ''];
        yield 'too long city' => ['12 rue des Lilas', '75001', str_repeat('a', 256)];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Address::of('12 rue des Lilas', '75001', 'Paris');
        $b = Address::of('  12 rue des Lilas  ', '  75001  ', '  Paris  ');
        $differentStreet = Address::of('8 avenue Foch', '75001', 'Paris');
        $differentPostalCode = Address::of('12 rue des Lilas', '75116', 'Paris');
        $differentCity = Address::of('12 rue des Lilas', '75001', 'Lyon');

        // When
        $equalResult = $a->equals($b);
        $differentStreetResult = $a->equals($differentStreet);
        $differentPostalCodeResult = $a->equals($differentPostalCode);
        $differentCityResult = $a->equals($differentCity);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentStreetResult);
        self::assertFalse($differentPostalCodeResult);
        self::assertFalse($differentCityResult);
    }
}
