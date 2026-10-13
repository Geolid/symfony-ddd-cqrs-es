<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<OrderId>
 */
final class OrderIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return OrderId::class;
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
