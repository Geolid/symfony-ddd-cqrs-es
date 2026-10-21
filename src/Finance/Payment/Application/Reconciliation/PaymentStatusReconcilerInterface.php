<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\PaymentStatus;

interface PaymentStatusReconcilerInterface
{
    public function supports(PaymentStatus $status): bool;

    public function reconcile(string $id, string $reference): void;
}
