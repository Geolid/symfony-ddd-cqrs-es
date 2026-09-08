<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Order\Application\Command\ApproveOrdersErasureOfBuyer\ApproveOrdersErasureOfBuyer;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.approve_orders_erasure_on_buyer_erased')]
final readonly class ApproveOrdersErasureOnBuyerErased
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
        $this->commandBus->dispatch(new ApproveOrdersErasureOfBuyer($event->buyerId));
    }
}
