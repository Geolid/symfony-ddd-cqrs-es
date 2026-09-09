<?php

declare(strict_types=1);

namespace Shared\Application\Mapper;

use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class PostalAddressMapper
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $data
     */
    public static function fromArray(array $data): PostalAddress
    {
        return PostalAddress::of($data['recipientName'], Address::of(...$data['address']));
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    public static function toArray(PostalAddress $postalAddress): array
    {
        return [
            'recipientName' => $postalAddress->recipientName,
            'address' => [
                'street' => $postalAddress->address->street,
                'postalCode' => $postalAddress->address->postalCode,
                'city' => $postalAddress->address->city,
                'countryCode' => $postalAddress->address->countryCode->value,
            ],
        ];
    }
}
