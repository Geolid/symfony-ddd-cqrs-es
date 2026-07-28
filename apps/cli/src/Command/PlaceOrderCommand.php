<?php

declare(strict_types=1);

namespace Cli\Command;

use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
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

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The customer placing the order')] string $customerId,
        #[Argument(description: 'Order total, in cents')] int $totalAmountInCents,
    ): int {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new PlaceOrder(
                id: $id,
                customerId: $customerId,
                totalAmountInCents: $totalAmountInCents,
            ));

            $io->success(\sprintf('Order %s placed.', $id));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
