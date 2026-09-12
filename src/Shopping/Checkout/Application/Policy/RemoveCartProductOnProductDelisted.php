<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Catalog\Listing\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\RemoveCartProduct\RemoveCartProduct;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;

#[Policy('shopping.checkout.remove_cart_product_on_product_delisted')]
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
