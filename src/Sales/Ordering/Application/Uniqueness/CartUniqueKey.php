<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Uniqueness;

enum CartUniqueKey: string
{
    case BUYER = 'sales.ordering.cart.buyer';
}
