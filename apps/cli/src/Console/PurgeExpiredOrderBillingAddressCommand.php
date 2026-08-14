<?php

declare(strict_types=1);

namespace Cli\Console;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Command\EraseOrderBillingAddress\EraseOrderBillingAddress;
use Sales\Order\Application\Query\ListOrdersWithExpiredBillingRetention\ListOrdersWithExpiredBillingRetention;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'sales:order:purge-expired-billing-address', description: 'Erase the billing address of every Order past its retention period')]
#[AsCronTask('0 0 * * *')]
final class PurgeExpiredOrderBillingAddressCommand
{
    use LockableTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly ClockInterface $clock,
        #[Autowire(param: 'sales.billing_retention_days')]
        private readonly int $billingRetentionDays,
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
            $cutoff = $this->clock->now()->modify(\sprintf('-%d days', $this->billingRetentionDays))->format(\DateTimeInterface::ATOM);
            $expired = $this->queryBus->ask(new ListOrdersWithExpiredBillingRetention($cutoff));
            $total = \count($expired);

            foreach ($expired as $order) {
                $this->commandBus->dispatch(new EraseOrderBillingAddress($order->id));
                $io->writeln(\sprintf('Erased billing address for order %s', $order->id));
            }

            $io->success(\sprintf('%d order(s) purged.', $total));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
