<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Support\Factory;

use Finance\Payment\Application\PSP\PaymentLine;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\QuantityFactory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<PaymentLine>
 */
final class PaymentLineFactory extends ObjectFactory
{
    public static function class(): string
    {
        return PaymentLine::class;
    }

    public function withLabel(Label $label): self
    {
        return $this->with(['label' => $label]);
    }

    public function withUnitPrice(Money $unitPrice): self
    {
        return $this->with(['unitPrice' => $unitPrice]);
    }

    public function withQuantity(Quantity $quantity): self
    {
        return $this->with(['quantity' => $quantity]);
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::withConstructor());
    }

    protected function defaults(): array
    {
        return [
            'label' => LabelFactory::new(),
            'unitPrice' => MoneyFactory::new(),
            'quantity' => QuantityFactory::new(),
        ];
    }
}
