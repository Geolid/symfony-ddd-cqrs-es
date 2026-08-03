<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\ListProductForSale;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ProductId;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Money;

#[AsCommandHandler]
final readonly class ListProductForSaleHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ListProductForSale $command): void
    {
        $this->repository->save(Product::list(
            ProductId::fromString($command->id),
            $command->label,
            Money::fromCents($command->unitAmountInCents),
            $this->clock->now(),
        ));
    }
}
