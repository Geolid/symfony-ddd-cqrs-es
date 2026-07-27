<?php

declare(strict_types=1);

namespace Cli\Command;

use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'order:place', description: 'Place an Order from the command line (demo/local seeding)')]
final class PlaceOrderCommand extends Command
{
    use LockableTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('customerId', InputArgument::REQUIRED, 'The customer placing the order')
            ->addArgument('totalAmountInCents', InputArgument::REQUIRED, 'Order total, in cents');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new PlaceOrder(
                id: $id,
                customerId: (string) $input->getArgument('customerId'),
                totalAmountInCents: (int) $input->getArgument('totalAmountInCents'),
            ));

            $io->success(\sprintf('Order %s placed.', $id));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
