<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Application\Command\AuthorizePayment\AuthorizePayment;
use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Application\Reconciliation\Exception\UnsupportedPaymentStatusException;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class PaymentReconciler implements PaymentReconcilerInterface
{
    public function __construct(
        private PaymentFinderInterface $paymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws PaymentResultNotFoundException
     * @throws UnsupportedPaymentStatusException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $paymentId): void
    {
        $payment = $this->paymentFinder->ofId($paymentId);

        if (!$payment->status->isRequested()) {
            throw UnsupportedPaymentStatusException::forStatus($payment->status);
        }

        $command = match ($this->paymentGateway->checkStatus($payment->reference)) {
            PaymentGatewayStatus::AUTHORIZED => new AuthorizePayment($paymentId),
            default => new AbandonPayment($paymentId),
        };

        $this->commandBus->dispatch($command);
    }
}
