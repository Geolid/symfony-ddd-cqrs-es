<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.confirm_order_on_payment_authorized')]
final readonly class ConfirmOrderOnPaymentAuthorized
{
    public function __construct(
        private CartFinderInterface $cartFinder,
        private CartLineFinderInterface $cartLineFinder,
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
        $cart = $this->cartFinder->ofId($event->cartId);

        $lines = [];
        foreach ($this->cartLineFinder->byCart($event->cartId) as $line) {
            $lines[] = [
                'lineId' => $line->lineId,
                'productId' => $line->productId,
                'label' => $line->label,
                'unitPriceInCents' => $line->unitPriceInCents,
                'quantity' => $line->quantity,
            ];
        }

        $this->commandBus->dispatch(new ConfirmOrder(
            id: OrderId::forCart($event->cartId)->toString(),
            cartId: $event->cartId,
            shopperId: $cart->shopperId,
            paymentId: $event->paymentId,
            lines: $lines,
        ));
    }
}
