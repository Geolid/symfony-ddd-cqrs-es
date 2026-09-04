<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Checkout\PaymentGatewayStatus;
use Finance\Payment\Application\Command\ConfirmPaymentRefund\ConfirmPaymentRefund;
use Finance\Payment\Application\PaymentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class RefundInitiatedPaymentReconciler implements PaymentStatusReconcilerInterface
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(PaymentStatus $status): bool
    {
        return PaymentStatus::REFUND_INITIATED === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        if (PaymentGatewayStatus::REFUNDED !== $this->paymentGateway->checkStatus($reference)) {
            return false;
        }

        $this->commandBus->dispatch(new ConfirmPaymentRefund($id));

        return true;
    }
}
