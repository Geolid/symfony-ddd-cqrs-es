<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\EraseShopper;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[CommandHandler]
final readonly class EraseShopperHandler
{
    public function __construct(
        private ShopperRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperNotFoundException
     * @throws ShopperAlreadyExistsException
     */
    public function __invoke(EraseShopper $command): void
    {
        $shopper = $this->repository->load(ShopperId::fromString($command->id));
        $shopper->erase($this->clock->now());

        $this->repository->save($shopper);

        $this->uniqueValues->release(UniqueKey::for(ShopperUniqueKey::EMAIL), $shopper->id->toString());
    }
}
