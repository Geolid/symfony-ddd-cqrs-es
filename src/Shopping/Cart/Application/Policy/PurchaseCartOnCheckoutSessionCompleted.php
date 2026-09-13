<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Cart\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;

#[Policy('shopping.cart.purchase_cart_on_checkout_session_completed')]
final readonly class PurchaseCartOnCheckoutSessionCompleted
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
        $this->commandBus->dispatch(new PurchaseCart($event->cartId));
    }
}
