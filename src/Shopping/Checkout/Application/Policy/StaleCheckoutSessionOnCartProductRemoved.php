<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Cart\Event\CartProductRemoved;

#[Policy('shopping.checkout.stale_checkout_session_on_cart_product_removed')]
final readonly class StaleCheckoutSessionOnCartProductRemoved
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CartProductRemoved::class)]
    public function __invoke(CartProductRemoved $event): void
    {
        $checkoutSession = $this->checkoutSessionFinder->ofCartOrNull($event->id->toString());
        if (null === $checkoutSession) {
            return;
        }

        $this->commandBus->dispatch(new StaleCheckoutSession($checkoutSession->id));
    }
}
