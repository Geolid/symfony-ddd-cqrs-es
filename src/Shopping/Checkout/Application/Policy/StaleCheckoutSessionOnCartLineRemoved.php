<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Cart\Event\CartLineRemoved;

#[Policy('shopping.checkout.stale_checkout_session_on_cart_line_removed')]
final readonly class StaleCheckoutSessionOnCartLineRemoved
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
    #[Subscribe(CartLineRemoved::class)]
    public function __invoke(CartLineRemoved $event): void
    {
        $checkoutSession = $this->checkoutSessionFinder->ofCartOrNull($event->id);
        if (null === $checkoutSession) {
            return;
        }

        $this->commandBus->dispatch(new StaleCheckoutSession($checkoutSession->id));
    }
}
