<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared\ShipmentPreparedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\PrepareOrder\PrepareOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.prepare_order_on_shipment_prepared')]
final readonly class PrepareOrderOnShipmentPrepared
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentPreparedIntegrationEvent::class)]
    public function __invoke(ShipmentPreparedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->reference))) {
            return;
        }

        $this->commandBus->dispatch(new PrepareOrder($event->reference));
    }
}
