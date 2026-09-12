<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;

#[Policy('sales.ordering.confirm_order_on_checkout_session_completed')]
final readonly class ConfirmOrderOnCheckoutSessionCompleted
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CheckoutSessionCompletedIntegrationEvent::class)]
    public function __invoke(CheckoutSessionCompletedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ConfirmOrder(
            id: OrderId::forCart($event->cartId)->toString(),
            cartId: $event->cartId,
            shopperId: $event->shopperId,
            checkoutSessionId: $event->checkoutSessionId,
            lines: $event->items,
            shippingAddress: $event->shippingAddress,
        ));
    }
}
