<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\ValueObject\PostalAddress;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<PostalAddress>
 */
final class PostalAddressFactory extends ObjectFactory
{
    public static function class(): string
    {
        return PostalAddress::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return [
            'recipientName' => faker()->name(),
            'address' => AddressFactory::new(),
        ];
    }
}
