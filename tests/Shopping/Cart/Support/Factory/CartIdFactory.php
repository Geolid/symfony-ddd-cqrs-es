<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Support\Factory;

use Ramsey\Uuid\Uuid;
use Shopping\Cart\Domain\ValueObject\CartId;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<CartId>
 */
final class CartIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return CartId::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['id' => Uuid::uuid7()->toString()];
    }
}
