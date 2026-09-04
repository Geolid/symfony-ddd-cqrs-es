<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Warehouse;

use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class WarehouseAddressProvider
{
    private PostalAddress $address;

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     */
    public function __construct(array $address)
    {
        $this->address = PostalAddress::of(
            $address['recipientName'],
            Address::of($address['street'], $address['postalCode'], $address['city'], $address['countryCode']),
        );
    }

    public function get(): PostalAddress
    {
        return $this->address;
    }
}
