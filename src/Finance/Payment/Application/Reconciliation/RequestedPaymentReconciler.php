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
        return PaymentStatus::REQUESTED === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        return match ($this->paymentGateway->checkStatus($reference)) {
            PaymentGatewayStatus::AUTHORIZED => $this->authorize($id),
            default => $this->abandon($id),
        };
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function authorize(string $id): bool
    {
        $this->commandBus->dispatch(new AuthorizePayment($id));

        return true;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function abandon(string $id): bool
    {
        $this->commandBus->dispatch(new AbandonPayment($id));

        return true;
    }
}
