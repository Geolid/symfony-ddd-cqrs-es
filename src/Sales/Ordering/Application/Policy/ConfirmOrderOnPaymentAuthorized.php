<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Sales\Ordering\Application\Finder\CheckoutSessionLine\CheckoutSessionLineFinderInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.confirm_order_on_payment_authorized')]
final readonly class ConfirmOrderOnPaymentAuthorized
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private CheckoutSessionLineFinderInterface $checkoutSessionLineFinder,
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

        $lines = [];
        foreach ($this->checkoutSessionLineFinder->byCheckoutSession($event->checkoutSessionId) as $line) {
            $lines[] = [
                'lineId' => $line->lineId,
                'productId' => $line->productId,
                'label' => $line->label,
                'unitPriceInCents' => $line->unitPriceInCents,
                'quantity' => $line->quantity,
            ];
        }

        $this->commandBus->dispatch(new ConfirmOrder(
            id: OrderId::forCart($checkoutSession->cartId)->toString(),
            cartId: $checkoutSession->cartId,
            shopperId: $checkoutSession->shopperId,
            paymentId: $event->paymentId,
            lines: $lines,
            shippingAddress: [
                'recipientName' => $checkoutSession->shippingAddress->recipientName,
                'address' => (array) $checkoutSession->shippingAddress->address,
            ],
            billingAddress: [
                'recipientName' => $checkoutSession->billingAddress->recipientName,
                'address' => (array) $checkoutSession->billingAddress->address,
            ],
        ));
    }
}
