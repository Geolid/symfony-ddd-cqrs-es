<?php

declare(strict_types=1);

namespace Cli\Command;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shipping\Shipment\Application\Query\ListPendingShipments\ListPendingShipments;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'shipment:dispatch-pending', description: 'Dispatch every Shipment still pending carrier pickup')]
final class DispatchPendingShipmentsCommand
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
            /** @var ListResult<ShipmentResult> $pending */
            $pending = $this->queryBus->ask(new ListPendingShipments(itemsPerPage: 100));

            foreach ($pending->items as $shipment) {
                $this->commandBus->dispatch(new DispatchShipment($shipment->id));
                $io->writeln(\sprintf('Dispatched shipment %s (order %s)', $shipment->id, $shipment->orderId));
            }

            $io->success(\sprintf('%d shipment(s) dispatched.', \count($pending->items)));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
