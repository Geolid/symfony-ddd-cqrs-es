<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Shared\Application\DrivingPort;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerNotRegisteredException;

#[DrivingPort]
interface CheckoutSessionOpenerInterface
{
    /**
     * @throws CustomerNotRegisteredException
     * @throws CustomerErasureRequestedException
     * @throws CustomerAddressesNotCompletedException
     */
    public function openFor(string $cartId): OpenedCheckoutSession;
}
