<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ChangeCartProductQuantity;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Exception\CartProductNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[CommandHandler]
final readonly class ChangeCartProductQuantityHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartAlreadyPurchasedException
     * @throws CartProductNotFoundException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(ChangeCartProductQuantity $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->changeQuantity($command->productId, Quantity::of($command->quantity), $this->clock->now());
        $this->repository->save($cart);
    }
}
