<?php

declare(strict_types=1);

namespace Cron\Console;

use Fulfilment\Shipping\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThreshold;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentReconcilerInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'fulfilment:shipment:reconcile', description: 'Poll the carrier for every Shipment stuck past its reconciliation threshold')]
#[AsCronTask('0 * * * *')]
final class ReconcileShipmentsCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ShipmentReconcilerInterface $shipmentReconciler,
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
            $stuck = $this->queryBus->ask(new ListShipmentsPastReconciliationThreshold());
            $polled = 0;

            foreach ($stuck as $shipment) {
                try {
                    $this->shipmentReconciler->reconcile($shipment->id);
                    ++$polled;
                } catch (ApplicationExceptionInterface $e) {
                    ++$failed;
                    $io->error(\sprintf('Failed to reconcile shipment %s: %s', $shipment->id, $e->getMessage()));
                }
            }

            $summary = \sprintf('%d shipment(s) polled, %d failed.', $polled, $failed);

            0 === $failed ? $io->success($summary) : $io->error($summary);
        } finally {
            $this->release();
        }

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }
}
