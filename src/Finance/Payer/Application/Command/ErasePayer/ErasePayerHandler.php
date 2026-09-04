<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\ErasePayer;

use Finance\Payer\Domain\Exception\PayerAlreadyExistsException;
use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ErasePayerHandler
{
    public function __construct(
        private PayerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PayerNotFoundException
     * @throws PayerAlreadyExistsException
     */
    public function __invoke(ErasePayer $command): void
    {
        $payer = $this->repository->load(PayerId::fromString($command->id));
        $payer->erase($this->clock->now());

        $this->repository->save($payer);
    }
}
