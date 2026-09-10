<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\StartCart;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Uniqueness\CartUniqueKey;
use Sales\Ordering\Application\Uniqueness\Exception\CartAlreadyActiveException;
use Sales\Ordering\Domain\Cart\Cart;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class StartCartHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartAlreadyActiveException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(StartCart $command): void
    {
        $id = CartId::fromString($command->id);

        try {
            $this->uniqueValues->claim(UniqueKey::for(CartUniqueKey::BUYER), $command->buyerId, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw CartAlreadyActiveException::forBuyer($command->buyerId, $e);
        }

        $cart = Cart::start($id, $command->buyerId, $this->clock->now());

        try {
            $this->repository->save($cart);
        } catch (CartAlreadyExistsException) {
            return;
        }
    }
}
