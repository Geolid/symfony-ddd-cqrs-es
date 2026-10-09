<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Factory;

use Shopping\Checkout\Domain\ValueObject\TaxRate;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<TaxRate>
 */
final class TaxRateFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TaxRate::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromBasisPoints'));
    }

    protected function defaults(): array
    {
        return ['basisPoints' => 2_000];
    }
}
