<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\ValueObject\Quantity;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<Quantity>
 */
final class QuantityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return Quantity::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return ['value' => faker()->numberBetween(1, 5)];
    }
}
