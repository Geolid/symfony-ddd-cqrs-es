<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\PaymentStatus;

interface PaymentStatusReconcilerInterface
{
    public function supports(PaymentStatus $status): bool;

    /**
     * @return bool whether the provider reported a transition, and it was applied
     */
    public function reconcile(string $id, string $reference): bool;
}
