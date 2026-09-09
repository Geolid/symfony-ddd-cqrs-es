<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.approve_shipments_erasure_on_buyer_erased')]
final readonly class ApproveShipmentsErasureOnBuyerErased
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
    #[Subscribe(BuyerErasedIntegrationEvent::class)]
    public function __invoke(BuyerErasedIntegrationEvent $event): void
    {
        foreach ($this->shipmentFinder->byBuyer($event->buyerId) as $shipment) {
            $this->commandBus->dispatch(new ApproveShipmentErasure($shipment->id));
        }
    }
}
