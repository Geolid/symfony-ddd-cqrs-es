<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface OrderPaymentRequesterInterface
{
    /**
     * @return string the checkout URL the buyer should be redirected to
     *
     * @throws ResultNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     */
    public function requestFor(string $orderId, string $returnUrl): string;
}
