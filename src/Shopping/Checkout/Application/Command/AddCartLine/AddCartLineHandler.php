<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AddCartLine;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Application\Command\AddCartLine\Exception\ProductNotListedException;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[CommandHandler]
final readonly class AddCartLineHandler
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
    public function __invoke(AddCartLine $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));

        $listedProducts = iterator_to_array($this->listedProductFinder->byIds($command->productId)->indexBy(
            static fn (ListedProductResult $product): string => $product->productId,
        ));
        $listedProduct = $listedProducts[$command->productId] ?? null;
        if (!$listedProduct instanceof ListedProductResult) {
            throw ProductNotListedException::forId($command->productId);
        }

        $cart->addLine(
            Product::of($listedProduct->productId, Label::fromString($listedProduct->label), Money::fromCents($listedProduct->unitPriceInCents)),
            Quantity::of($command->quantity),
            $this->clock->now(),
        );

        $this->repository->save($cart);
    }
}
