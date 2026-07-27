<?php

declare(strict_types=1);

namespace Demo\Ordering;

use Demo\Ordering\Input\SeedOrdersInput;
use Demo\Shared\WeightedPicker;
use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Domain\OrderStatus;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fixtures dispatch through the real Command bus — see the CommandBusInterface docblock in
 * Support\Helpers\CqrsTrait's test equivalent (tests.md) for why: an event-sourced app has no
 * "just INSERT a row" shortcut, since the read model is a Projector's derived output.
 */
#[AsCommand(name: 'demo:ordering:orders', description: 'Seed orders (and, by fan-out, their shipments)')]
final readonly class SeedOrdersCommand
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput]
        SeedOrdersInput $input,
    ): int {
        $weights = [
            OrderStatus::PLACED->value => $input->placedWeight,
            OrderStatus::CANCELLED->value => $input->cancelledWeight,
        ];
        $stats = array_fill_keys(array_keys($weights), 0);

        $io->progressStart($input->count);

        for ($i = 1; $i <= $input->count; ++$i) {
            $id = Uuid::uuid7()->toString();
            $customerId = \sprintf('%s-%d', $input->customer, random_int(1, max(1, (int) ($input->count / 4))));
            $totalAmountInCents = random_int(500, 25_000);

            $this->commandBus->dispatch(new PlaceOrder($id, $customerId, $totalAmountInCents));

            $status = OrderStatus::from(WeightedPicker::pick($weights));

            if (OrderStatus::CANCELLED === $status) {
                $this->commandBus->dispatch(new CancelOrder($id));
            }

            ++$stats[$status->value];
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(\sprintf(
            '%d order(s) seeded (placed: %d, cancelled: %d).',
            array_sum($stats),
            $stats[OrderStatus::PLACED->value],
            $stats[OrderStatus::CANCELLED->value],
        ));

        return Command::SUCCESS;
    }
}
