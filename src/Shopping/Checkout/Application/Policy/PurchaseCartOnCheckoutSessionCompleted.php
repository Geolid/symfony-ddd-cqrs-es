<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionCompleted;

#[Policy('shopping.checkout.purchase_cart_on_checkout_session_completed')]
final readonly class PurchaseCartOnCheckoutSessionCompleted
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CheckoutSessionCompleted::class)]
    public function __invoke(CheckoutSessionCompleted $event): void
    {
        $this->commandBus->dispatch(new PurchaseCart($event->cartId));
    }
}
