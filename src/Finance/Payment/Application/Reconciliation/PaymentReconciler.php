<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\Exception\UnsupportedPaymentStatusException;
use Finance\Payment\Application\PaymentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class PaymentReconciler implements PaymentReconcilerInterface
{
    /**
     * @param iterable<PaymentStatusReconcilerInterface> $reconcilers
     */
    public function __construct(private iterable $reconcilers)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, PaymentStatus $status, string $reference): bool
    {
        foreach ($this->reconcilers as $reconciler) {
            if ($reconciler->supports($status)) {
                return $reconciler->reconcile($id, $reference);
            }
        }

        throw UnsupportedPaymentStatusException::forStatus($status);
    }
}
