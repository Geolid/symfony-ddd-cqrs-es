<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Shared\Application\DrivingPort;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CartPricesStaleException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperNotRegisteredException;

#[DrivingPort]
interface CheckoutSessionOpenerInterface
{
    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperErasureRequestedException
     * @throws ShopperAddressesNotCompletedException
     * @throws CartPricesStaleException
     */
    public function openFor(string $cartId): OpenedCheckoutSession;
}
