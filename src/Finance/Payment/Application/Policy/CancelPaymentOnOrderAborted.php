<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\CancelPayment\CancelPayment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderAborted\OrderAbortedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.cancel_payment_on_order_aborted')]
final readonly class CancelPaymentOnOrderAborted
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderAbortedIntegrationEvent::class)]
    public function __invoke(OrderAbortedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelPayment(
            PaymentId::forOrder($event->orderId)->toString(),
        ));
    }
}
