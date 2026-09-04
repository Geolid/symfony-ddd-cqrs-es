<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\ValueObject;

enum BuyerUniqueKey: string
{
    case EMAIL = 'sales.buyer.buyer.email';
}
