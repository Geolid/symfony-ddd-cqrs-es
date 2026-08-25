<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Shared\Application\Port\DrivingPort;

#[DrivingPort]
interface OrderPaymentRequesterInterface
{
    /**
     * @return string the checkout URL the buyer should be redirected to
     *
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentRequestInProgressException
     */
    public function requestFor(string $orderId, string $returnUrl): string;
}
