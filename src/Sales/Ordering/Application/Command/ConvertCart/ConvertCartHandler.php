<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConvertCart;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\CartUniqueKey;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class ConvertCartHandler
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
    public function __invoke(ConvertCart $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->convert($this->clock->now());
        $this->repository->save($cart);

        $this->uniqueValues->release(UniqueKey::for(CartUniqueKey::BUYER), $command->id);
    }
}
