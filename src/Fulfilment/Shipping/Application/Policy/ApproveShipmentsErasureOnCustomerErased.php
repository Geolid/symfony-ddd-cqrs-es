<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.approve_shipments_erasure_on_customer_erased')]
final readonly class ApproveShipmentsErasureOnCustomerErased
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CustomerErasedIntegrationEvent::class)]
    public function __invoke(CustomerErasedIntegrationEvent $event): void
    {
        foreach ($this->shipmentFinder->byCustomer($event->customerId) as $shipment) {
            $this->commandBus->dispatch(new ApproveShipmentErasure($shipment->id));
        }
    }
}
