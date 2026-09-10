<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Cart;

use Shared\Application\DrivingPort;
use Shopping\Checkout\Application\Cart\Exception\CartOutdatedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperNotRegisteredException;

#[DrivingPort]
interface SubmitCartInterface
{
    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperErasureRequestedException
     * @throws ShopperAddressesNotCompletedException
     * @throws CartOutdatedException
     */
    public function submit(string $cartId): SubmitCartResult;
}
