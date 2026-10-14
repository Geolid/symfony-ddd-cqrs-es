<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\Service\CooldownCalculator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<CooldownCalculator>
 */
final class CooldownCalculatorFactory extends ObjectFactory
{
    public static function class(): string
    {
        return CooldownCalculator::class;
    }

    protected function defaults(): array
    {
        return ['seconds' => faker()->numberBetween(30, 900)];
    }
}
