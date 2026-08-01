<?php

declare(strict_types=1);

namespace Demo\Sales;

use Demo\Sales\Input\SeedCustomersInput;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Domain\CustomerId;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'demo:sales:customers', description: 'Seed customers')]
final readonly class SeedCustomersCommand
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput]
        SeedCustomersInput $input,
    ): int {
        $io->progressStart($input->count);

        for ($i = 1; $i <= $input->count; ++$i) {
            $this->commandBus->dispatch(new RegisterCustomer(
                CustomerId::generate()->toString(),
                \sprintf('buyer-%d@%s', $i, $input->domain),
            ));

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(\sprintf('%d customer(s) seeded.', $input->count));

        return Command::SUCCESS;
    }
}
