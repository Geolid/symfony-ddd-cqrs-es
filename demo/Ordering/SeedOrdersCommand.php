<?php

declare(strict_types=1);

namespace Demo\Ordering;

use Demo\Ordering\Input\SeedOrdersInput;
use Demo\Shared\WeightedPicker;
use Ordering\Order\Domain\OrderStatus;
use Ordering\Order\Domain\Repository\OrderRepositoryInterface;
use Ordering\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'demo:ordering:orders', description: 'Seed orders (and, by fan-out, their shipments)')]
final readonly class SeedOrdersCommand
{
    public function __construct(private OrderRepositoryInterface $repository)
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
            $customerId = \sprintf('%s-%d', $input->customer, random_int(1, max(1, (int) ($input->count / 4))));

            $factory = OrderTestFactory::new()
                ->withCustomerId($customerId)
                ->withTotalAmountInCents(random_int(500, 25_000));

            $status = OrderStatus::from(WeightedPicker::pick($weights));

            $order = match ($status) {
                OrderStatus::CANCELLED => $factory->cancelled()->create(),
                default => $factory->create(),
            };

            $this->repository->save($order);

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
