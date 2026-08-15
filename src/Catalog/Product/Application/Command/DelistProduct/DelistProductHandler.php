<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\DelistProduct;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class DelistProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFoundException
     */
    public function __invoke(DelistProduct $command): void
    {
        $product = $this->repository->load(ProductId::fromString($command->id));
        $product->delist($this->clock->now());

        $this->repository->save($product);
    }
}
