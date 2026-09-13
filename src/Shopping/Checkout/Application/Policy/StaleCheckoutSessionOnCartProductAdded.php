<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Cart\Application\IntegrationEvent\CartProductAdded\CartProductAddedIntegrationEvent;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;

#[Policy('shopping.checkout.stale_checkout_session_on_cart_product_added')]
final readonly class StaleCheckoutSessionOnCartProductAdded
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
    #[Subscribe(CartProductAddedIntegrationEvent::class)]
    public function __invoke(CartProductAddedIntegrationEvent $event): void
    {
        $checkoutSession = $this->checkoutSessionFinder->ofCartOrNull($event->cartId);
        if (null === $checkoutSession) {
            return;
        }

        $this->commandBus->dispatch(new StaleCheckoutSession($checkoutSession->id));
    }
}
