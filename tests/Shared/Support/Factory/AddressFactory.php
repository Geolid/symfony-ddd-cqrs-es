<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\ValueObject\Address;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<Address>
 */
final class AddressFactory extends ObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return [
            'street' => faker()->streetAddress(),
            'postalCode' => faker()->postcode(),
            'city' => faker()->city(),
            'countryCode' => faker()->countryCode(),
        ];
    }
}
