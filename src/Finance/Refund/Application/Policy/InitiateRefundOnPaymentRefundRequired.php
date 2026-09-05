<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRequired\PaymentRefundRequiredIntegrationEvent;
use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.refund.initiate_refund_on_payment_refund_required')]
final readonly class InitiateRefundOnPaymentRefundRequired
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentRefundRequiredIntegrationEvent::class)]
    public function __invoke(PaymentRefundRequiredIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new InitiateRefund($event->orderId));
    }
}
