<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Policy;

use Catalog\Listing\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Cart\Application\Command\RemoveCartProduct\RemoveCartProduct;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface;

#[Policy('shopping.cart.remove_cart_product_on_product_delisted')]
final readonly class RemoveCartProductOnProductDelisted
{
    public function __construct(
        private CartFinderInterface $cartFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ProductDelistedIntegrationEvent::class)]
    public function __invoke(ProductDelistedIntegrationEvent $event): void
    {
        foreach ($this->cartFinder->byProductId($event->productId) as $cart) {
            $this->commandBus->dispatch(new RemoveCartProduct($cart->id, $event->productId));
        }
    }
}
