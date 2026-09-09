<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Warehouse;

use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class WarehouseAddressProvider
{
    private PostalAddress $address;

    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $address
     */
    public function __construct(array $address)
    {
        $this->address = PostalAddressMapper::fromArray($address);
    }

    public function get(): PostalAddress
    {
        return $this->address;
    }
}
