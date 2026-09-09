<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\AddCartLine;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Command\AddCartLine\Exception\ProductNotListedException;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyConvertedException;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartCheckoutInProgressException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

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
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
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
