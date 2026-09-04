<?php

declare(strict_types=1);

namespace Cli\Console;

use AfterSales\Withdrawal\Application\Command\ApproveWithdrawal\ApproveWithdrawal;
use AfterSales\Withdrawal\Application\Command\RejectWithdrawal\RejectWithdrawal;
use Cli\Console\Input\InspectWithdrawalInput;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'after-sales:withdrawal:inspect', description: 'Record the quality control outcome of a received Withdrawal')]
final readonly class InspectWithdrawalCommand
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] InspectWithdrawalInput $input): int
    {
        $reason = $input->reject;

        if ($input->approve === (null !== $reason)) {
            $io->error('Specify exactly one of --approve or --reject=<reason>.');

            return Command::FAILURE;
        }

        if (null === $reason) {
            $this->commandBus->dispatch(new ApproveWithdrawal($input->orderId));
            $io->success(\sprintf('Withdrawal for order %s approved.', $input->orderId));

            return Command::SUCCESS;
        }

        $this->commandBus->dispatch(new RejectWithdrawal($input->orderId, $reason));
        $io->success(\sprintf('Withdrawal for order %s rejected.', $input->orderId));

        return Command::SUCCESS;
    }
}
