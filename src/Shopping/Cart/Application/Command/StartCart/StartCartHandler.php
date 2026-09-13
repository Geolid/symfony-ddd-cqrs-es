<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\StartCart;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Cart\Application\CartUniqueKey;
use Shopping\Cart\Application\Command\StartCart\Exception\CartAlreadyActiveException;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Repository\CartRepositoryInterface;
use Shopping\Cart\Domain\ValueObject\CartId;

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
            $this->uniqueValues->claim(UniqueKey::for(CartUniqueKey::CUSTOMER), $command->customerId, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw CartAlreadyActiveException::forCustomer($command->customerId, $e);
        }

        $cart = Cart::start($id, $command->customerId, $this->clock->now());

        try {
            $this->repository->save($cart);
        } catch (CartAlreadyExistsException) {
            return;
        }
    }
}
