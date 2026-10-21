<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\PaymentStatus;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface PaymentReconcilerInterface
{
    public function reconcile(string $id, PaymentStatus $status, string $reference): void;
}
