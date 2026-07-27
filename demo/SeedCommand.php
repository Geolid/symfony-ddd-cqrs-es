<?php

declare(strict_types=1);

namespace Demo;

use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fixtures for an event-sourced app can't be a bare INSERT: the read model is a Projector's
 * derived output, so seeding replays the same Commands a real caller would send, through the
 * same Command bus — never a direct write to the projection tables. This is that example;
 * copy its shape for a new fixture set.
 *
 * Placing an Order also triggers Shipping's CreateShipmentOnOrderPlaced Processor (via the
 * Integration Event translator) — in dev, patchlevel's catch-up subscriptions pick it up on the
 * next request/command, so the corresponding Shipment shows up shortly after this command runs
 * (`make cc` or reload the web/api DM once to force it).
 */
#[AsCommand(name: 'demo:seed', description: 'Seed a handful of orders through the real Command bus')]
final class SeedCommand extends Command
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orders = [
            ['customer-1', 1_999],
            ['customer-1', 4_250],
            ['customer-2', 12_000],
        ];

        foreach ($orders as [$customerId, $totalAmountInCents]) {
            $this->commandBus->dispatch(new PlaceOrder(Uuid::uuid7()->toString(), $customerId, $totalAmountInCents));
        }

        $io->success(\sprintf('Placed %d order(s).', \count($orders)));

        return Command::SUCCESS;
    }
}
