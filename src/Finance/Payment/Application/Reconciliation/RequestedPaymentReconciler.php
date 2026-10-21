<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Application\Command\AuthorizePayment\AuthorizePayment;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class RequestedPaymentReconciler implements PaymentStatusReconcilerInterface
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(PaymentStatus $status): bool
    {
        return $status->isRequested();
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): void
    {
        $command = match ($this->paymentGateway->checkStatus($reference)) {
            PaymentGatewayStatus::AUTHORIZED => new AuthorizePayment($id),
            default => new AbandonPayment($id),
        };

        $this->commandBus->dispatch($command);
    }
}
