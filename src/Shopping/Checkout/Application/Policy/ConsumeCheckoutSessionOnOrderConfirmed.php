<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\ConsumeCheckoutSession\ConsumeCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;

#[Policy('shopping.checkout.consume_checkout_session_on_order_confirmed')]
final readonly class ConsumeCheckoutSessionOnOrderConfirmed
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
    #[Subscribe(OrderConfirmedIntegrationEvent::class)]
    public function __invoke(OrderConfirmedIntegrationEvent $event): void
    {
        $checkoutSession = $this->checkoutSessionFinder->ofCartOrNull($event->cartId);
        if (null === $checkoutSession) {
            return;
        }

        $this->commandBus->dispatch(new ConsumeCheckoutSession($checkoutSession->id));
    }
}
