<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Factory;

use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\QuantityFactory;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<CheckoutItem>
 */
final class CheckoutItemFactory extends ObjectFactory
{
    public static function class(): string
    {
        return CheckoutItem::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('of'));
    }

    protected function defaults(): array
    {
        return [
            'productId' => Uuid::uuid7()->toString(),
            'label' => LabelFactory::new(),
            'unitPrice' => MoneyFactory::new(),
            'quantity' => QuantityFactory::new(),
            'taxRate' => TaxRateFactory::new(),
        ];
    }
}
