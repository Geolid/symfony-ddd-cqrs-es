<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer\CancelOrphanedOrdersOfCustomer;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.order.cancel_orders_on_customer_erased')]
final readonly class CancelOrdersOnCustomerErased
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CustomerErasedIntegrationEvent::class)]
    public function __invoke(CustomerErasedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelOrphanedOrdersOfCustomer($event->customerId));
    }
}
