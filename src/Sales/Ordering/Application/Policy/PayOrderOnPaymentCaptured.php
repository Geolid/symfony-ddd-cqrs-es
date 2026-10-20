<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\PayOrder\PayOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.pay_order_on_payment_captured')]
final readonly class PayOrderOnPaymentCaptured
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentCapturedIntegrationEvent::class)]
    public function __invoke(PaymentCapturedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new PayOrder($event->orderId));
    }
}
