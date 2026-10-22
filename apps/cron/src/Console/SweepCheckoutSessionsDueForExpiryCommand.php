<?php

declare(strict_types=1);

namespace Cron\Console;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shopping\Checkout\Application\Command\ExpireCheckoutSession\ExpireCheckoutSession;
use Shopping\Checkout\Application\Query\ListCheckoutSessionsDueForExpiry\ListCheckoutSessionsDueForExpiry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(name: 'shopping:checkout:sweep-due-for-expiry', description: 'Expire every open CheckoutSession past its expiry')]
#[AsCronTask('*/5 * * * *')]
final class SweepCheckoutSessionsDueForExpiryCommand
{
    use LockableTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
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
            $due = $this->queryBus->ask(new ListCheckoutSessionsDueForExpiry());
            $count = 0;

            foreach ($due as $checkoutSession) {
                $this->commandBus->dispatch(new ExpireCheckoutSession($checkoutSession->id));
                ++$count;
            }

            $io->success(\sprintf('%d checkout session(s) due for expiry dispatched.', $count));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
