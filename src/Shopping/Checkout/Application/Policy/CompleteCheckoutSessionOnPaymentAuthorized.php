<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\CompleteCheckoutSession\CompleteCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;

#[Policy('shopping.checkout.complete_checkout_session_on_payment_authorized')]
final readonly class CompleteCheckoutSessionOnPaymentAuthorized
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
    #[Subscribe(PaymentAuthorizedIntegrationEvent::class)]
    public function __invoke(PaymentAuthorizedIntegrationEvent $event): void
    {
        $checkoutSession = $this->checkoutSessionFinder->ofId($event->checkoutSessionId);

        $items = [];
        foreach ($checkoutSession->items as $item) {
            $items[] = [
                'productId' => $item->productId,
                'label' => $item->label,
                'unitPriceInCents' => $item->unitPriceInCents,
                'quantity' => $item->quantity,
            ];
        }

        $this->commandBus->dispatch(new CompleteCheckoutSession(
            id: $checkoutSession->id,
            cartId: $checkoutSession->cartId,
            customerId: $checkoutSession->customerId,
            items: $items,
            currency: $checkoutSession->currency,
            taxRateBasisPoints: $checkoutSession->taxRateBasisPoints,
            shippingAddress: [
                'recipientName' => $checkoutSession->shippingAddress->recipientName,
                'address' => (array) $checkoutSession->shippingAddress->address,
            ],
            billingAddress: [
                'recipientName' => $checkoutSession->billingAddress->recipientName,
                'address' => (array) $checkoutSession->billingAddress->address,
            ],
            paymentId: $event->paymentId,
        ));
    }
}
