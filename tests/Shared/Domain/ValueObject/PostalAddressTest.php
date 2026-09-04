<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\CountryCode;
use Shared\Domain\ValueObject\PostalAddress;

final class PostalAddressTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $recipientName): void
    {
        // Given
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');

        // When
        $postalAddress = PostalAddress::of($recipientName, $address);

        // Then
        self::assertSame($recipientName, $postalAddress->recipientName);
        self::assertSame('10 Rue de la Paix', $postalAddress->address->street);
        self::assertSame('75002', $postalAddress->address->postalCode);
        self::assertSame('Paris', $postalAddress->address->city);
        self::assertSame(CountryCode::FR, $postalAddress->address->countryCode);
        $primitivePostalAddress = $postalAddress->toArray();
        self::assertSame(
            ['recipientName' => $recipientName, 'street' => '10 Rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'countryCode' => 'FR'],
            $primitivePostalAddress,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'recipient name' => ['John Doe'];
        yield 'maximum length' => [str_repeat('a', PostalAddress::RECIPIENT_NAME_MAX_LENGTH)];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $recipientName): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PostalAddress::of($recipientName, Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty recipient name' => [''];
        yield 'whitespace-only recipient name' => ['   '];
        yield 'too long recipient name' => [str_repeat('a', PostalAddress::RECIPIENT_NAME_MAX_LENGTH + 1)];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');
        $a = PostalAddress::of('John Doe', $address);
        $b = PostalAddress::of('  John Doe  ', $address);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $address = Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR');
        $a = PostalAddress::of('John Doe', $address);

        $differentName = PostalAddress::of('Jane Smith', $address);
        $differentAddress = PostalAddress::of('John Doe', Address::of('8 Avenue Foch', '75016', 'Paris', 'FR'));

        // When
        $differsOnName = $a->equals($differentName);
        $differsOnAddress = $a->equals($differentAddress);

        // Then
        self::assertFalse($differsOnName);
        self::assertFalse($differsOnAddress);
    }
}
