<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\RemoveCartProduct;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Exception\CartAlreadyPurchasedException;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\Exception\CartProductNotFoundException;
use Shopping\Cart\Domain\Repository\CartRepositoryInterface;
use Shopping\Cart\Domain\ValueObject\CartId;

#[CommandHandler]
final readonly class RemoveCartProductHandler
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
    public function __invoke(RemoveCartProduct $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->removeProduct($command->productId, $this->clock->now());
        $this->repository->save($cart);
    }
}
