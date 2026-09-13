<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Entity;

use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Sales\Ordering\Domain\Order\ValueObject\OrderLineId;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\TaxedAmount;

final readonly class OrderLine
{
    public function __construct(
        public OrderLineId $id,
        public OrderItem $item,
    ) {
    }

    public function total(): Money
    {
        return $this->item->total();
    }

    public function taxedTotal(): TaxedAmount
    {
        return $this->item->taxedTotal();
    }
}
