<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface OrderPaymentRequesterInterface
{
    /**
     * @return string the checkout URL the buyer should be redirected to
     *
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     */
    public function requestFor(string $orderId, string $returnUrl): string;
}
