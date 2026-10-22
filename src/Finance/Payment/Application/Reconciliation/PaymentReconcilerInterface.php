<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Reconciliation\Exception\UnsupportedPaymentStatusException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface PaymentReconcilerInterface
{
    /**
     * @throws PaymentResultNotFoundException
     * @throws UnsupportedPaymentStatusException
     */
    public function reconcile(string $paymentId): void;
}
