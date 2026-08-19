<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface OrderPaymentReconcilerInterface
{
    /**
     * @return bool whether the provider reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
