<?php

declare(strict_types=1);

namespace Cron\Console;

use Compliance\Erasing\Application\Command\ApproveErasure\ApproveErasure;
use Compliance\Erasing\Application\Query\ListErasuresDueForApproval\ListErasuresDueForApproval;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'compliance:erasing:sweep-due-for-approval', description: 'Approve every Erasure still REQUESTED past its effective date')]
#[AsCronTask('0 * * * *')]
final class SweepErasuresDueForApprovalCommand
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
            $due = $this->queryBus->ask(new ListErasuresDueForApproval());
            $count = 0;

            foreach ($due as $erasure) {
                $this->commandBus->dispatch(new ApproveErasure($erasure->id));
                ++$count;
            }

            $io->success(\sprintf('%d erasure(s) due for approval dispatched.', $count));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
