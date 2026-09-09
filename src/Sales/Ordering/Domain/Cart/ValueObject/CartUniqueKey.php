<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\ValueObject;

enum CartUniqueKey: string
{
    case BUYER = 'sales.ordering.cart.buyer';
}
