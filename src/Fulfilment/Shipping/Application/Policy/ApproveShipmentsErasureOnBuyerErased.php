<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\ApproveShipmentsErasureOfBuyer\ApproveShipmentsErasureOfBuyer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.approve_shipments_erasure_on_buyer_erased')]
final readonly class ApproveShipmentsErasureOnBuyerErased
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(BuyerErasedIntegrationEvent::class)]
    public function __invoke(BuyerErasedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ApproveShipmentsErasureOfBuyer($event->buyerId));
    }
}
