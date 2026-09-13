<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Mapper;

use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final readonly class CheckoutItemMapper
{
    /**
     * @param array{productId: string, label: string, unitPriceInCents: int, quantity: int} $data
     */
    public static function fromArray(array $data, Currency $currency, TaxRate $taxRate): CheckoutItem
    {
        return CheckoutItem::of(
            $data['productId'],
            Label::fromString($data['label']),
            Money::fromCents($data['unitPriceInCents'], $currency->value),
            Quantity::of($data['quantity']),
            $taxRate,
        );
    }

    /**
     * @return array{productId: string, label: string, unitPriceInCents: int, quantity: int}
     */
    public static function toArray(CheckoutItem $item): array
    {
        return [
            'productId' => $item->productId,
            'label' => $item->label->value,
            'unitPriceInCents' => $item->unitPrice->cents,
            'quantity' => $item->quantity->value,
        ];
    }
}
