<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\RepriceProduct;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandUseCase;
use Shared\Domain\ValueObject\Money;

#[CommandUseCase]
final readonly class RepriceProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFoundException
     */
    public function __invoke(RepriceProduct $command): void
    {
        $product = $this->repository->load(ProductId::fromString($command->id));
        $product->reprice(Money::fromCents($command->unitAmountInCents), $this->clock->now());

        $this->repository->save($product);
    }
}
