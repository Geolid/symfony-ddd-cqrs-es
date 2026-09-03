<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\CountryCode;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class PostalAddressTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $fullName = FullName::of('John', 'Doe');
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');

        // When
        $postalAddress = PostalAddress::of($fullName, $address);

        // Then
        self::assertSame('John', $postalAddress->fullName->firstName);
        self::assertSame('Doe', $postalAddress->fullName->lastName);
        self::assertSame('10 Rue de la Paix', $postalAddress->address->street);
        self::assertSame('75002', $postalAddress->address->postalCode);
        self::assertSame('Paris', $postalAddress->address->city);
        self::assertSame(CountryCode::FR, $postalAddress->address->countryCode);
        self::assertSame('John Doe, 10 Rue de la Paix, 75002 Paris, FR', $postalAddress->toString());
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $fullName = FullName::of('John', 'Doe');
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');
        $a = PostalAddress::of($fullName, $address);
        $b = PostalAddress::of($fullName, $address);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $fullName = FullName::of('John', 'Doe');
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');
        $a = PostalAddress::of($fullName, $address);

        $differentName = PostalAddress::of(FullName::of('Jane', 'Smith'), $address);
        $differentAddress = PostalAddress::of($fullName, Address::of('8 Avenue Foch', '75016', 'Paris', 'FR'));

        // When
        $differsOnName = $a->equals($differentName);
        $differsOnAddress = $a->equals($differentAddress);

        // Then
        self::assertFalse($differsOnName);
        self::assertFalse($differsOnAddress);
    }
}
