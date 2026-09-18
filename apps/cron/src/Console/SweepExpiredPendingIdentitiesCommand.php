<?php

declare(strict_types=1);

namespace Cron\Console;

use Iam\Identity\Application\Command\ErasePendingIdentity\ErasePendingIdentity;
use Iam\Identity\Application\Query\ListExpiredPendingIdentities\ListExpiredPendingIdentities;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'iam:identity:sweep-expired-pending', description: 'Erase every Identity still PENDING past its confirmation window')]
#[AsCronTask('0 * * * *')]
final class SweepExpiredPendingIdentitiesCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io): int
    {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $expired = $this->queryBus->ask(new ListExpiredPendingIdentities());
            $count = 0;

            foreach ($expired as $identity) {
                $this->commandBus->dispatch(new ErasePendingIdentity($identity->id));
                ++$count;
            }

            $io->success(\sprintf('%d expired pending identit(y/ies) dispatched for erasure.', $count));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
