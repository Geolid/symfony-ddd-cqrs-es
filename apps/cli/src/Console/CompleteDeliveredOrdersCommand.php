<?php

declare(strict_types=1);

namespace Cli\Console;

use Sales\Order\Application\Command\CompleteOrder\CompleteOrder;
use Sales\Order\Application\Query\ListDeliveredOrders\ListDeliveredOrders;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'sales:order:complete-delivered', description: 'Complete every Order whose return window has elapsed')]
#[AsCronTask('0 0 * * *')]
final class CompleteDeliveredOrdersCommand
{
    use LockableTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
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
            $delivered = $this->queryBus->ask(new ListDeliveredOrders());
            $total = 0;

            foreach ($delivered as $order) {
                $this->commandBus->dispatch(new CompleteOrder($order->id));
                $io->writeln(\sprintf('Checked order %s for completion', $order->id));
                ++$total;
            }

            $io->success(\sprintf('%d delivered order(s) checked for completion.', $total));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
