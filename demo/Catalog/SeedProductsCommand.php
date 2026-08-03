<?php

declare(strict_types=1);

namespace Demo\Catalog;

use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Demo\Catalog\Input\SeedProductsInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'demo:catalog:products', description: 'Seed the product catalog')]
final readonly class SeedProductsCommand
{
    private const array CATALOG = [
        ['label' => 'Espresso cups, set of 6', 'unitAmountInCents' => 1_750],
        ['label' => 'Saucer', 'unitAmountInCents' => 83],
        ['label' => 'French press, 1L', 'unitAmountInCents' => 3_200],
        ['label' => 'Coffee grinder', 'unitAmountInCents' => 8_900],
        ['label' => 'Ceramic mug', 'unitAmountInCents' => 1_200],
        ['label' => 'Pour-over dripper', 'unitAmountInCents' => 2_400],
        ['label' => 'Milk frother', 'unitAmountInCents' => 1_500],
        ['label' => 'Coffee scale', 'unitAmountInCents' => 4_500],
    ];

    public function __construct(private ProductRepositoryInterface $repository)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput]
        SeedProductsInput $input,
    ): int {
        $io->progressStart(\count(self::CATALOG));

        $delistedCount = min($input->delistedCount, \count(self::CATALOG));

        foreach (self::CATALOG as $i => $entry) {
            $factory = ProductTestFactory::new()
                ->withLabel($entry['label'])
                ->withUnitAmountInCents($entry['unitAmountInCents'])
                ->withoutIncrementalIds();

            $product = $i < $delistedCount ? $factory->delisted()->create() : $factory->create();

            $this->repository->save($product);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(\sprintf('%d product(s) seeded (%d delisted).', \count(self::CATALOG), $delistedCount));

        return Command::SUCCESS;
    }
}
