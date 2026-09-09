<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\RemoveCartLine;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartLineNotFoundException;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyConvertedException;
use Sales\Ordering\Domain\Cart\Exception\CartCheckoutInProgressException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Shared\Application\Command\CommandHandler;

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
