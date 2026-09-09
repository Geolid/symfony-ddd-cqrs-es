<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\CancelBuyerErasure;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelBuyerErasureHandler
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotFoundException
     * @throws BuyerAlreadyExistsException
     */
    public function __invoke(CancelBuyerErasure $command): void
    {
        $buyer = $this->repository->load(BuyerId::fromString($command->id));
        $buyer->cancelErasure($this->clock->now());
        $this->repository->save($buyer);
    }
}
