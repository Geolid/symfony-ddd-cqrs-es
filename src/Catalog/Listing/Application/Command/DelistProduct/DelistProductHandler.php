<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\DelistProduct;

use Catalog\Listing\Application\Uniqueness\ProductUniqueKey;
use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class DelistProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFoundException
     * @throws ProductAlreadyExistsException
     */
    public function __invoke(DelistProduct $command): void
    {
        $product = $this->repository->load(ProductId::fromString($command->id));
        $product->delist($this->clock->now());

        $this->repository->save($product);

        $this->uniqueValues->release(UniqueKey::for(ProductUniqueKey::LABEL), $product->id->toString());
    }
}
