<?php

declare(strict_types=1);

namespace Cli\Console;

use Fulfilment\Shipment\Application\Command\PrepareShipment\PrepareShipment;
use Fulfilment\Shipment\Application\Query\ListRequestedShipments\ListRequestedShipments;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'fulfilment:shipment:prepare-requested', description: 'Prepare every requested Shipment once its parcel has been packed')]
final class PrepareRequestedShipmentsCommand
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
            $requested = $this->queryBus->ask(new ListRequestedShipments());
            $total = \count($requested);

            foreach ($requested as $shipment) {
                $this->commandBus->dispatch(new PrepareShipment($shipment->id));
                $io->writeln(\sprintf('Prepared shipment %s (order %s)', $shipment->id, $shipment->orderId));
            }

            $io->success(\sprintf('%d shipment(s) prepared.', $total));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
