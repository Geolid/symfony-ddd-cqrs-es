<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Order\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.approve_orders_erasure_on_buyer_erased')]
final readonly class ApproveOrdersErasureOnBuyerErased
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
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
        foreach ($this->orderFinder->byBuyer($event->buyerId) as $order) {
            $this->commandBus->dispatch(new ApproveOrderErasure($order->id));
        }
    }
}
