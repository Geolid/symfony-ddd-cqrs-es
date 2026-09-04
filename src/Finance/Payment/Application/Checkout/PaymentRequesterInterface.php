<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Finance\Payment\Application\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Exception\PlacedOrderAlreadyCancelledException;
use Finance\Payment\Application\Exception\PlacedOrderResultNotFoundException;
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
