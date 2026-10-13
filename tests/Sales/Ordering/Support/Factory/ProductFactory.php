<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Product>
 */
final class ProductFactory extends ObjectFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'label' => LabelFactory::new(),
            'price' => MoneyFactory::new(),
        ];
    }
}
