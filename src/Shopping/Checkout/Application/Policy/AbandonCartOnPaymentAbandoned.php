<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAbandoned\PaymentAbandonedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\AbandonCartCheckout\AbandonCartCheckout;

#[Policy('shopping.checkout.abandon_cart_on_payment_abandoned')]
final readonly class AbandonCartOnPaymentAbandoned
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentAbandonedIntegrationEvent::class)]
    public function __invoke(PaymentAbandonedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new AbandonCartCheckout($event->cartId));
    }
}
