<?php

declare(strict_types=1);

namespace Cli\Console;

use Fulfilment\Shipment\Application\Carrier\ShipmentReconcilerInterface;
use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThreshold;
use Psr\Log\LoggerInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'fulfilment:shipment:reconcile', description: 'Poll Acme for every Shipment stuck past its reconciliation threshold')]
#[AsCronTask('0 * * * *')]
final class ReconcileShipmentsCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ShipmentReconcilerInterface $shipmentReconciler,
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
            $stuck = $this->queryBus->ask(new ListShipmentsPastReconciliationThreshold());
            $reconciled = 0;

            foreach ($stuck as $shipment) {
                try {
                    if ($this->shipmentReconciler->reconcile($shipment->id, $shipment->status->value, $shipment->trackingReference, $shipment->returnTrackingReference)) {
                        ++$reconciled;
                        $io->writeln(\sprintf('Reconciled shipment %s', $shipment->id));
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to reconcile shipment {id}', [
                        'id' => $shipment->id,
                        'reference' => $shipment->trackingReference ?? $shipment->returnTrackingReference,
                        'exception' => $e,
                    ]);
                    $io->error(\sprintf('Failed to reconcile shipment %s: %s', $shipment->id, $e->getMessage()));
                }
            }

            $io->success(\sprintf('%d shipment(s) reconciled.', $reconciled));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
