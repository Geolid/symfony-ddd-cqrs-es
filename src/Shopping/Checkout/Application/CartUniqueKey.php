<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application;

enum CartUniqueKey: string
{
    case CUSTOMER = 'shopping.checkout.cart.customer';
}
