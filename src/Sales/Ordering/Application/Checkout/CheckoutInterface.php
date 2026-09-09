<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout;

use Sales\Ordering\Application\Checkout\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface CheckoutInterface
{
    /**
     * @throws BuyerNotRegisteredException
     * @throws BuyerErasureRequestedException
     * @throws BuyerAddressesNotCompletedException
     * @throws CartOutdatedException
     */
    public function checkout(string $cartId): CheckoutResult;
}
