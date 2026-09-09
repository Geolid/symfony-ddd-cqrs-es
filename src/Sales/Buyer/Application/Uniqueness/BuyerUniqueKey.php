<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Uniqueness;

enum BuyerUniqueKey: string
{
    case EMAIL = 'sales.buyer.buyer.email';
}
