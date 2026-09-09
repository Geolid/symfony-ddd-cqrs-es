<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAbandoned\PaymentAbandonedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\AbandonCartCheckout\AbandonCartCheckout;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.abandon_cart_on_payment_abandoned')]
final readonly class AbandonCartOnPaymentAbandoned
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentAbandonedIntegrationEvent::class)]
    public function __invoke(PaymentAbandonedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new AbandonCartCheckout($event->cartId));
    }
}
