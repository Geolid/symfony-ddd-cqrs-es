<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final class PostalAddressMapperTest extends TestCase
{
    #[Test]
    public function itConvertsToArray(): void
    {
        // Given
        $postalAddress = PostalAddress::of('John Doe', Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR'));

        // When
        $array = PostalAddressMapper::toArray($postalAddress);

        // Then
        self::assertSame(
            [
                'recipientName' => 'John Doe',
                'address' => ['street' => '10 Rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'countryCode' => 'FR'],
            ],
            $array,
        );
    }

    #[Test]
    public function itConvertsFromArray(): void
    {
        // Given
        $data = [
            'recipientName' => 'John Doe',
            'address' => ['street' => '10 Rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'countryCode' => 'FR'],
        ];

        // When
        $postalAddress = PostalAddressMapper::fromArray($data);

        // Then
        self::assertTrue($postalAddress->equals(PostalAddress::of('John Doe', Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR'))));
    }
}
