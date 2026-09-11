<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CartCheckedOut;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\Cart\Event\CartCheckedOut;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

#[Publisher('shopping.checkout.publish_cart_checked_out')]
final readonly class CartCheckedOutPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private CartRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws CartNotFoundException
     */
    #[Subscribe(CartCheckedOut::class)]
    public function __invoke(CartCheckedOut $event): void
    {
        $cart = $this->repository->load(CartId::fromString($event->id));

        $this->publisher->publish(Cart::class, $event->id, new CartCheckedOutIntegrationEvent(
            cartId: $event->id,
            shopperId: $event->shopperId,
            lines: array_map(static fn (Line $line): array => [
                'lineId' => $line->id->toString(),
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'unitPriceInCents' => $line->product->price->cents,
                'quantity' => $line->quantity->value,
            ], $cart->lines()),
            totalAmountInCents: $event->totalAmountInCents,
            checkedOutAt: $event->checkedOutAt,
        ));
    }
}
