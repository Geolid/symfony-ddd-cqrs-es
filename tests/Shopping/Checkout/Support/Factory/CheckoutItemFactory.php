<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Factory;

use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\QuantityFactory;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\TaxRate;
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

    public function withUnitPrice(Money $unitPrice): self
    {
        return $this->with(['unitPrice' => $unitPrice]);
    }

    public function withQuantity(Quantity $quantity): self
    {
        return $this->with(['quantity' => $quantity]);
    }

    public function withTaxRate(TaxRate $taxRate): self
    {
        return $this->with(['taxRate' => $taxRate]);
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
