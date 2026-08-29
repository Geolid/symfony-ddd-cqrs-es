<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Status\OrderPaymentStatus;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface OrderPaymentReconcilerInterface
{
    /**
     * @return bool whether the provider reported a transition, and it was applied
     */
    public function reconcile(string $id, OrderPaymentStatus $status, string $reference): bool;
}
