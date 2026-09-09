<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Command\ConvertCart\ConvertCart;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Shared\Entity\Line;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.confirm_order_on_payment_authorized')]
final readonly class ConfirmOrderOnPaymentAuthorized
{
    public function __construct(
        private CartRepositoryInterface $repository,
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
        $cart = $this->repository->load(CartId::fromString($event->cartId));

        $this->commandBus->dispatch(new ConfirmOrder(
            id: OrderId::forCart($event->cartId)->toString(),
            buyerId: $cart->buyerId,
            paymentId: $event->paymentId,
            lines: array_map(static fn (Line $line): array => [
                'lineId' => $line->id->toString(),
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'unitPriceInCents' => $line->product->price->cents,
                'quantity' => $line->quantity->value,
            ], $cart->lines()),
        ));

        $this->commandBus->dispatch(new ConvertCart($event->cartId));
    }
}
