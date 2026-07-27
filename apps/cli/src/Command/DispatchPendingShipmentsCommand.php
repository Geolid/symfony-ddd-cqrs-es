<?php

declare(strict_types=1);

namespace Cli\Command;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;
use Shipping\Shipment\Domain\ShipmentStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A batch job run from an ops box or a scheduled container — the same Command bus a web
 * request would use, just triggered by cron instead of HTTP. One kernel, another Delivery
 * Mechanism.
 */
#[AsCommand(name: 'shipment:dispatch-pending', description: 'Dispatch every Shipment still pending carrier pickup')]
final class DispatchPendingShipmentsCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var ListResult<ShipmentResult> $pending */
        $pending = $this->queryBus->ask(new ListShipments(status: ShipmentStatus::PENDING->value, itemsPerPage: 100));

        foreach ($pending->items as $shipment) {
            $this->commandBus->dispatch(new DispatchShipment($shipment->id));
            $io->writeln(\sprintf('Dispatched shipment %s (order %s)', $shipment->id, $shipment->orderId));
        }

        $io->success(\sprintf('%d shipment(s) dispatched.', \count($pending->items)));

        return Command::SUCCESS;
    }
}
