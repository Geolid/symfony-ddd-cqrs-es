<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Money;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<Money>
 */
final class MoneyFactory extends ObjectFactory
{
    public static function class(): string
    {
        return Money::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromCents'));
    }

    protected function defaults(): array
    {
        return ['cents' => faker()->numberBetween(500, 5_000), 'currency' => Currency::EUR->value];
    }
}
