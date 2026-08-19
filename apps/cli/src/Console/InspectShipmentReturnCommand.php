<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\InspectShipmentReturnInput;
use Fulfilment\Shipment\Application\Command\ApproveShipmentReturn\ApproveShipmentReturn;
use Fulfilment\Shipment\Application\Command\RejectShipmentReturn\RejectShipmentReturn;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'fulfilment:shipment:inspect-return', description: 'Record the quality control outcome of a received Shipment return')]
final readonly class InspectShipmentReturnCommand
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] InspectShipmentReturnInput $input): int
    {
        $reason = $input->reject;

        if ($input->approve === (null !== $reason)) {
            $io->error('Specify exactly one of --approve or --reject=<reason>.');

            return Command::FAILURE;
        }

        if (null === $reason) {
            $this->commandBus->dispatch(new ApproveShipmentReturn($input->shipmentId));
            $io->success(\sprintf('Return of shipment %s approved.', $input->shipmentId));

            return Command::SUCCESS;
        }

        $this->commandBus->dispatch(new RejectShipmentReturn($input->shipmentId, $reason));
        $io->success(\sprintf('Return of shipment %s rejected.', $input->shipmentId));

        return Command::SUCCESS;
    }
}
