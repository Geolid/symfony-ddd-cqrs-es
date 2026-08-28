<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Status\OrderPaymentStatus;

interface OrderPaymentStatusReconcilerInterface
{
    public function supports(OrderPaymentStatus $status): bool;

    /**
     * @return bool whether the provider reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
