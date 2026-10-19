<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\Factory\PostalAddressFactory;

final class PostalAddressMapperTest extends TestCase
{
    #[Test]
    public function itMapsToArray(): void
    {
        // Given
        $postalAddress = PostalAddressFactory::new()->create();

        // When
        $array = PostalAddressMapper::toArray($postalAddress);

        // Then
        self::assertSame(
            [
                'recipientName' => $postalAddress->recipientName,
                'address' => [
                    'street' => $postalAddress->address->street,
                    'postalCode' => $postalAddress->address->postalCode,
                    'city' => $postalAddress->address->city,
                    'countryCode' => $postalAddress->address->countryCode->value,
                ],
            ],
            $array,
        );
    }

    #[Test]
    public function itMapsFromArray(): void
    {
        // Given
        $source = PostalAddressFactory::new()->create();
        $data = [
            'recipientName' => $source->recipientName,
            'address' => [
                'street' => $source->address->street,
                'postalCode' => $source->address->postalCode,
                'city' => $source->address->city,
                'countryCode' => $source->address->countryCode->value,
            ],
        ];

        // When
        $postalAddress = PostalAddressMapper::fromArray($data);

        // Then
        self::assertSame($source->recipientName, $postalAddress->recipientName);
        self::assertSame($source->address->street, $postalAddress->address->street);
        self::assertSame($source->address->postalCode, $postalAddress->address->postalCode);
        self::assertSame($source->address->city, $postalAddress->address->city);
        self::assertSame($source->address->countryCode, $postalAddress->address->countryCode);
    }
}
