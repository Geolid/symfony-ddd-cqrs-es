<?php

declare(strict_types=1);

namespace Shopping\Cart\Application;

enum CartUniqueKey: string
{
    case CUSTOMER = 'shopping.cart.cart.customer';
}
