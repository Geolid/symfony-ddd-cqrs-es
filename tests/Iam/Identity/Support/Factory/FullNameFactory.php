<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\ValueObject\FullName;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<FullName>
 */
final class FullNameFactory extends ObjectFactory
{
    public static function class(): string
    {
        return FullName::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['value' => faker()->name()];
    }
}
