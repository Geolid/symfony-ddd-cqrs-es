<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Checkout\Exception\PlacedOrderAlreadyCancelledException;
use Finance\Payment\Application\Finder\PlacedOrder\Exception\PlacedOrderResultNotFoundException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface PaymentRequesterInterface
{
    /**
     * @return string the checkout URL the buyer should be redirected to
     *
     * @throws PlacedOrderResultNotFoundException
     * @throws PlacedOrderAlreadyCancelledException
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $orderId, string $returnUrl): string;
}
