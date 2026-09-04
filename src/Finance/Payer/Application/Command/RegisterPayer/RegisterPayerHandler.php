<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\RegisterPayer;

use Finance\Payer\Domain\Exception\PayerAlreadyExistsException;
use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RegisterPayerHandler
{
    public function __construct(
        private PayerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PayerAlreadyExistsException
     */
    public function __invoke(RegisterPayer $command): void
    {
        $payer = Payer::register(
            id: PayerId::fromString($command->id),
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($payer);
    }
}
