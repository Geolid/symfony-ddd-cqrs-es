<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface RequestOrderPaymentInterface
{
    /**
     * @throws OrderResultNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     */
    public function requestFor(string $orderId): void;
}
