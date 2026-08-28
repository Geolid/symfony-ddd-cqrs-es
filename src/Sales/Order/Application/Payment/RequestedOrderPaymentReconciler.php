<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Command\AuthorizeOrderPayment\AuthorizeOrderPayment;
use Sales\Order\Application\Command\FailOrderPayment\FailOrderPayment;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class RequestedOrderPaymentReconciler implements OrderPaymentStatusReconcilerInterface
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(string $status): bool
    {
        return OrderPaymentStatus::REQUESTED->value === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        return match ($this->paymentGateway->checkStatus($reference)) {
            OrderPaymentStatus::AUTHORIZED->value => $this->authorize($id),
            OrderPaymentStatus::FAILED->value => $this->fail($id),
            default => false,
        };
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function authorize(string $id): bool
    {
        $this->commandBus->dispatch(new AuthorizeOrderPayment($id));

        return true;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function fail(string $id): bool
    {
        $this->commandBus->dispatch(new FailOrderPayment($id));

        return true;
    }
}
