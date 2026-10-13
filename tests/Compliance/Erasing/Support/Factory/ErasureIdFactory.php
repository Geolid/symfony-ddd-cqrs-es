<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Support\Factory;

use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<ErasureId>
 */
final class ErasureIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return ErasureId::class;
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
