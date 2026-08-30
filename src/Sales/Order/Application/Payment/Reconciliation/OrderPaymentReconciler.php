<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment\Reconciliation;

use Sales\Order\Application\Exception\UnsupportedOrderPaymentStatusException;
use Sales\Order\Application\OrderPaymentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class OrderPaymentReconciler implements OrderPaymentReconcilerInterface
{
    /**
     * @param iterable<OrderPaymentStatusReconcilerInterface> $reconcilers
     */
    public function __construct(private iterable $reconcilers)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, OrderPaymentStatus $status, string $reference): bool
    {
        foreach ($this->reconcilers as $reconciler) {
            if ($reconciler->supports($status)) {
                return $reconciler->reconcile($id, $reference);
            }
        }

        throw UnsupportedOrderPaymentStatusException::forStatus($status);
    }
}
