<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application;

enum ShopperUniqueKey: string
{
    case EMAIL = 'shopping.checkout.shopper.email';
}
