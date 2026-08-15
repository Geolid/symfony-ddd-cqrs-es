<?php

declare(strict_types=1);

namespace Cli\Console;

use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Query\ListPendingShipments\ListPendingShipments;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'fulfilment:shipment:manifest-pending', description: 'Manifest every Shipment still pending carrier pickup')]
#[AsCronTask('0 0 * * *')]
final class ManifestPendingShipmentsCommand
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
            $pending = $this->queryBus->ask(new ListPendingShipments());
            $total = \count($pending);

            foreach ($pending as $shipment) {
                $this->commandBus->dispatch(new ManifestShipment($shipment->id));
                $io->writeln(\sprintf('Manifested shipment %s (order %s)', $shipment->id, $shipment->orderId));
            }

            $io->success(\sprintf('%d shipment(s) manifested.', $total));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
