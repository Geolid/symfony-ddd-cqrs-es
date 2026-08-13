<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\CancelShipmentsForCustomer\CancelShipmentsForCustomer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.cancel_shipments_on_customer_erased')]
final readonly class CancelShipmentsOnCustomerErased
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
        $this->commandBus->dispatch(new CancelShipmentsForCustomer($event->customerId));
    }
}
