<?php

declare(strict_types=1);

namespace Demo\Sales;

use Demo\Sales\Input\SeedOrdersInput;
use Demo\Shared\WeightedPicker;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\OrderState;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'demo:sales:orders', description: 'Seed orders (and, by fan-out, their shipments)')]
final readonly class SeedOrdersCommand
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private CustomerFinderInterface $customerFinder,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput]
        SeedOrdersInput $input,
    ): int {
        $weights = [
            OrderState::PLACED->value => $input->placedWeight,
            OrderState::CANCELLED->value => $input->cancelledWeight,
        ];
        $stats = array_fill_keys(array_keys($weights), 0);

        $customers = array_values(iterator_to_array($this->customerFinder));

        if ([] === $customers) {
            $io->error('No customer registered — run "demo:sales:customers" first.');

            return Command::FAILURE;
        }

        $io->progressStart($input->count);

        for ($i = 1; $i <= $input->count; ++$i) {
            $customer = $customers[array_rand($customers)];

            $factory = OrderTestFactory::new()
                ->withCustomerId($customer->id)
                ->withBuyerAddress($customer->email)
                ->withLines([OrderLine::of('Assorted goods', random_int(1, 5), Money::fromCents(random_int(500, 5_000)))])
                ->withoutIncrementalIds();

            $status = OrderState::from(WeightedPicker::pick($weights));

            $order = match ($status) {
                OrderState::CANCELLED => $factory->cancelled()->create(),
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
            $stats[OrderState::PLACED->value],
            $stats[OrderState::CANCELLED->value],
        ));

        return Command::SUCCESS;
    }
}
