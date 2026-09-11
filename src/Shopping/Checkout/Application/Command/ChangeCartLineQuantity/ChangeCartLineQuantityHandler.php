<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ChangeCartLineQuantity;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartLineNotFoundException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[CommandHandler]
final readonly class ChangeCartLineQuantityHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartAlreadyPurchasedException
     * @throws CartLineNotFoundException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(ChangeCartLineQuantity $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->changeQuantity(LineId::fromString($command->lineId), Quantity::of($command->quantity), $this->clock->now());
        $this->repository->save($cart);
    }
}
