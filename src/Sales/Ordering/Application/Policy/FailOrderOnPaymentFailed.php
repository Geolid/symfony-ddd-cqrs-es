<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentFailed\PaymentFailedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\FailOrder\FailOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.fail_order_on_payment_failed')]
final readonly class FailOrderOnPaymentFailed
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentFailedIntegrationEvent::class)]
    public function __invoke(PaymentFailedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new FailOrder($event->orderId));
    }
}
