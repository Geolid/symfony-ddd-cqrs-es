<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\ChangeCartProductQuantity;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Exception\CartAlreadyPurchasedException;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\Exception\CartProductNotFoundException;
use Shopping\Cart\Domain\Repository\CartRepositoryInterface;
use Shopping\Cart\Domain\ValueObject\CartId;
use Shopping\Cart\Domain\ValueObject\Quantity;

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
