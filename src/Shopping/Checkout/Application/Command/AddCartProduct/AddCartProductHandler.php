<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AddCartProduct;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Application\Command\AddCartProduct\Exception\ProductNotListedException;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[CommandHandler]
final readonly class AddCartProductHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ListedProductFinderInterface $listedProductFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartAlreadyPurchasedException
     * @throws CartAlreadyExistsException
     * @throws ProductNotListedException
     */
    public function __invoke(AddCartProduct $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));

        $listedProducts = iterator_to_array($this->listedProductFinder->byIds($command->productId)->indexBy(
            static fn (ListedProductResult $product): string => $product->productId,
        ));
        $listedProduct = $listedProducts[$command->productId] ?? null;
        if (!$listedProduct instanceof ListedProductResult) {
            throw ProductNotListedException::forId($command->productId);
        }

        $cart->addProduct($command->productId, Quantity::of($command->quantity), $this->clock->now());

        $this->repository->save($cart);
    }
}
