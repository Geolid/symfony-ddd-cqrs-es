<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\PrepareShipmentInput;
use Fulfilment\Shipment\Application\Command\PrepareShipment\PrepareShipment;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'fulfilment:shipment:prepare', description: 'Prepare a requested Shipment once its parcel has been packed')]
final class PrepareShipmentCommand
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] PrepareShipmentInput $input): int
    {
        $this->commandBus->dispatch(new PrepareShipment($input->shipmentId));

        $io->success(\sprintf('Shipment %s prepared.', $input->shipmentId));

        return Command::SUCCESS;
    }
}
