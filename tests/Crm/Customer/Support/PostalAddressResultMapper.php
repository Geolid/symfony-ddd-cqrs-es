<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Support;

use Crm\Customer\Application\Finder\Customer\PostalAddressResult;

final class PostalAddressResultMapper
{
    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    public static function toArray(PostalAddressResult $postalAddressResult): array
    {
        return [
            'recipientName' => $postalAddressResult->recipientName,
            'address' => [
                'street' => $postalAddressResult->address->street,
                'postalCode' => $postalAddressResult->address->postalCode,
                'city' => $postalAddressResult->address->city,
                'countryCode' => $postalAddressResult->address->countryCode,
            ],
        ];
    }
}
