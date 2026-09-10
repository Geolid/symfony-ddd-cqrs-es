<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RemoveCartLine;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyConvertedException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartCheckoutInProgressException;
use Shopping\Checkout\Domain\Cart\Exception\CartLineNotFoundException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;

#[CommandHandler]
final readonly class RemoveCartLineHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
     * @throws CartLineNotFoundException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(RemoveCartLine $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->removeLine(LineId::fromString($command->lineId), $this->clock->now());
        $this->repository->save($cart);
    }
}
