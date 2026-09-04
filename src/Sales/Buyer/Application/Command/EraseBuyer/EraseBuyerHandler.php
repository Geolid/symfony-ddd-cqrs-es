<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\EraseBuyer;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class EraseBuyerHandler
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotFoundException
     * @throws BuyerAlreadyExistsException
     */
    public function __invoke(EraseBuyer $command): void
    {
        $buyer = $this->repository->load(BuyerId::fromString($command->id));
        $buyer->erase($this->clock->now());

        $this->repository->save($buyer);

        $this->uniqueValues->release(UniqueKey::for(BuyerUniqueKey::EMAIL), $buyer->id->toString());
    }
}
