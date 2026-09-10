<?php

declare(strict_types=1);

namespace Sales\Ordering\Application;

enum CartUniqueKey: string
{
    case SHOPPER = 'sales.ordering.cart.shopper';
}
