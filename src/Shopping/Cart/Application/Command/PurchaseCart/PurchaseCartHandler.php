<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\PurchaseCart;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Cart\Application\CartUniqueKey;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\Repository\CartRepositoryInterface;
use Shopping\Cart\Domain\ValueObject\CartId;

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

        $this->uniqueValues->release(UniqueKey::for(CartUniqueKey::CUSTOMER), $command->id);
    }
}
