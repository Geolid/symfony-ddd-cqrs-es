<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class PostalAddressTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $fullName = FullName::of('Ada', 'Lovelace');
        $address = Address::of('12 rue des Lilas', '75001', 'Paris');

        // When
        $postalAddress = PostalAddress::of($fullName, $address);

        // Then
        self::assertTrue($fullName->equals($postalAddress->fullName));
        self::assertTrue($address->equals($postalAddress->address));
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $b = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $otherName = PostalAddress::of(FullName::of('Grace', 'Hopper'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $otherAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));

        // When
        $equalResult = $a->equals($b);
        $differentNameResult = $a->equals($otherName);
        $differentAddressResult = $a->equals($otherAddress);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentNameResult);
        self::assertFalse($differentAddressResult);
    }
}
