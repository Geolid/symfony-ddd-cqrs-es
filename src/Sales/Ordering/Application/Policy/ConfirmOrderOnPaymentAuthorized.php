<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.confirm_order_on_payment_authorized')]
final readonly class ConfirmOrderOnPaymentAuthorized
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentAuthorizedIntegrationEvent::class)]
    public function __invoke(PaymentAuthorizedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ConfirmOrder($event->orderId));
    }
}
