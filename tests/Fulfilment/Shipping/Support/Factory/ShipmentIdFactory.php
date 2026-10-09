<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Support\Factory;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<ShipmentId>
 */
final class ShipmentIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return ShipmentId::class;
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
