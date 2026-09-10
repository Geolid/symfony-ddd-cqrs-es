<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;

#[Policy('sales.ordering.approve_orders_erasure_on_shopper_erased')]
final readonly class ApproveOrdersErasureOnShopperErased
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
    #[Subscribe(ShopperErasedIntegrationEvent::class)]
    public function __invoke(ShopperErasedIntegrationEvent $event): void
    {
        foreach ($this->orderFinder->byShopper($event->shopperId) as $order) {
            $this->commandBus->dispatch(new ApproveOrderErasure($order->id));
        }
    }
}
