<?php

declare(strict_types=1);

namespace Cron\Console;

use Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold\ListPaymentsPastReconciliationThreshold;
use Finance\Payment\Application\Reconciliation\PaymentReconcilerInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'finance:payment:reconcile', description: 'Poll the payment provider for every Payment still requested past its reconciliation threshold')]
#[AsCronTask('*/15 * * * *')]
final class ReconcilePaymentsCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly PaymentReconcilerInterface $paymentReconciler,
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

        $failed = 0;

        try {
            $stuck = $this->queryBus->ask(new ListPaymentsPastReconciliationThreshold());
            $dispatched = 0;

            foreach ($stuck as $payment) {
                try {
                    if ($this->paymentReconciler->reconcile($payment->id, $payment->status, $payment->reference)) {
                        ++$dispatched;
                    }
                } catch (ApplicationExceptionInterface $e) {
                    ++$failed;
                    $io->error(\sprintf('Failed to reconcile payment %s: %s', $payment->id, $e->getMessage()));
                }
            }

            $io->success(\sprintf('%d payment(s) reconciliation dispatched.', $dispatched));
        } finally {
            $this->release();
        }

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }
}
