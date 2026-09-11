<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\PurchaseCart;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\CartUniqueKey;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

#[CommandHandler]
final readonly class PurchaseCartHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(PurchaseCart $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->purchase($this->clock->now());
        $this->repository->save($cart);

        $this->uniqueValues->release(UniqueKey::for(CartUniqueKey::SHOPPER), $command->id);
    }
}
