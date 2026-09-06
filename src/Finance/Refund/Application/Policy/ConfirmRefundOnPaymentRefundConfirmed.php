<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundConfirmed\PaymentRefundConfirmedIntegrationEvent;
use Finance\Refund\Application\Command\ConfirmRefund\ConfirmRefund;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.refund.confirm_refund_on_payment_refund_confirmed')]
final readonly class ConfirmRefundOnPaymentRefundConfirmed
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentRefundConfirmedIntegrationEvent::class)]
    public function __invoke(PaymentRefundConfirmedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ConfirmRefund($event->refundId));
    }
}
