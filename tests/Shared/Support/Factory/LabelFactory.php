<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Shared\Domain\ValueObject\Label;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<Label>
 */
final class LabelFactory extends ObjectFactory
{
    public static function class(): string
    {
        return Label::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['value' => faker()->unique()->words(2, true)];
    }
}
