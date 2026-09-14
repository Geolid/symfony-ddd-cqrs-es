<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Mapper;

use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;

final readonly class OrderItemMapper
{
    /**
     * @param array{productId: string, label: string, unitPriceInCents: int, quantity: int} $data
     */
    public static function fromArray(array $data): OrderItem
    {
        return OrderItem::of(
            Product::of($data['productId'], Label::fromString($data['label']), Money::fromCents($data['unitPriceInCents'])),
            Quantity::of($data['quantity']),
        );
    }
}
