<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;

#[Policy('fulfilment.shipping.approve_shipments_erasure_on_shopper_erased')]
final readonly class ApproveShipmentsErasureOnShopperErased
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
    #[Subscribe(ShopperErasedIntegrationEvent::class)]
    public function __invoke(ShopperErasedIntegrationEvent $event): void
    {
        foreach ($this->shipmentFinder->byShopper($event->shopperId) as $shipment) {
            $this->commandBus->dispatch(new ApproveShipmentErasure($shipment->id));
        }
    }
}
