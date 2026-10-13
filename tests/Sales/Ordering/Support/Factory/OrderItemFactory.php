<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Factory;

use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\QuantityFactory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<OrderItem>
 */
final class OrderItemFactory extends ObjectFactory
{
    public static function class(): string
    {
        return OrderItem::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return [
            'product' => ProductFactory::new(),
            'quantity' => QuantityFactory::new(),
            'taxAmount' => MoneyFactory::new(['cents' => faker()->numberBetween(50, 500)]),
        ];
    }
}
