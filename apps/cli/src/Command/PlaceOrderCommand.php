<?php

declare(strict_types=1);

namespace Cli\Command;

use Cli\Input\PlaceOrderInput;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'order:place', description: 'Place an Order from the command line (demo/local seeding)')]
final class PlaceOrderCommand
{
    use LockableTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] PlaceOrderInput $input): int
    {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new PlaceOrder(
                id: $id,
                customerId: $input->customerId,
                totalAmountInCents: $input->totalAmountInCents,
            ));

            $io->success(\sprintf('Order %s placed.', $id));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
