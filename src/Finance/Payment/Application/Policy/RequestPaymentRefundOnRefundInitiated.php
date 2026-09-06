<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\RequestPaymentRefund\RequestPaymentRefund;
use Finance\Refund\Application\IntegrationEvent\RefundInitiated\RefundInitiatedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.request_payment_refund_on_refund_initiated')]
final readonly class RequestPaymentRefundOnRefundInitiated
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(RefundInitiatedIntegrationEvent::class)]
    public function __invoke(RefundInitiatedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RequestPaymentRefund($event->paymentId, $event->refundId));
    }
}
