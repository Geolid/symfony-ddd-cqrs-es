<?php

declare(strict_types=1);

namespace Cli\Console;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Language\PublishedShipmentStatus;
use Fulfilment\Shipment\Application\Query\ListShipments\ListShipments;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'fulfilment:fulfilment:shipment:dispatch-pending', description: 'Dispatch every Shipment still pending carrier pickup')]
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
            $pending = $this->queryBus->ask(new ListShipments(status: PublishedShipmentStatus::PENDING->value, itemsPerPage: 100));

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
