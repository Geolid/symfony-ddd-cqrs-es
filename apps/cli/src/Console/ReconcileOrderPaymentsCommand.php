<?php

declare(strict_types=1);

namespace Cli\Console;

use Psr\Log\LoggerInterface;
use Sales\Order\Application\Payment\OrderPaymentReconcilerInterface;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThreshold;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'sales:order-payment:reconcile', description: 'Poll Globex for every OrderPayment stuck past its reconciliation threshold')]
#[AsCronTask('*/15 * * * *')]
final class ReconcileOrderPaymentsCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly OrderPaymentReconcilerInterface $orderPaymentReconciler,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function __invoke(SymfonyStyle $io): int
    {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $stuck = $this->queryBus->ask(new ListOrderPaymentsPastReconciliationThreshold());
            $reconciled = 0;

            foreach ($stuck as $orderPayment) {
                try {
                    if ($this->orderPaymentReconciler->reconcile($orderPayment->id, $orderPayment->status->value, $orderPayment->reference)) {
                        ++$reconciled;
                        $io->writeln(\sprintf('Reconciled order payment %s', $orderPayment->id));
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to reconcile order payment {id}', [
                        'id' => $orderPayment->id,
                        'reference' => $orderPayment->reference,
                        'exception' => $e,
                    ]);
                    $io->error(\sprintf('Failed to reconcile order payment %s: %s', $orderPayment->id, $e->getMessage()));
                }
            }

            $io->success(\sprintf('%d order payment(s) reconciled.', $reconciled));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
