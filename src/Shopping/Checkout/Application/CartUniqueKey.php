<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application;

enum CartUniqueKey: string
{
    case SHOPPER = 'shopping.checkout.cart.shopper';
}
