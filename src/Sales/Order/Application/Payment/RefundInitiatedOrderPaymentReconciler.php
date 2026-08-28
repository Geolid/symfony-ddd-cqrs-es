<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Command\ConfirmOrderPaymentRefund\ConfirmOrderPaymentRefund;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class RefundInitiatedOrderPaymentReconciler implements OrderPaymentStatusReconcilerInterface
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(OrderPaymentStatus $status): bool
    {
        return OrderPaymentStatus::REFUND_INITIATED === $status;
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

        $this->commandBus->dispatch(new ConfirmOrderPaymentRefund($id));

        return true;
    }
}
