<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout;

use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperNotRegisteredException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface CheckoutInterface
{
    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperErasureRequestedException
     * @throws ShopperAddressesNotCompletedException
     * @throws CartOutdatedException
     */
    public function checkout(string $cartId): CheckoutResult;
}
