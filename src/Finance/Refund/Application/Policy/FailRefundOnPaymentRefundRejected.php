<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected\PaymentRefundRejectedIntegrationEvent;
use Finance\Refund\Application\Command\FailRefund\FailRefund;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.refund.fail_refund_on_payment_refund_rejected')]
final readonly class FailRefundOnPaymentRefundRejected
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentRefundRejectedIntegrationEvent::class)]
    public function __invoke(PaymentRefundRejectedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new FailRefund($event->orderId));
    }
}
