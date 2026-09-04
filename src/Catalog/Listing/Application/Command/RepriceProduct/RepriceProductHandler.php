<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\RepriceProduct;

use Catalog\Listing\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class RepriceProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFoundException
     * @throws ProductAlreadyDelistedException
     * @throws ProductAlreadyExistsException
     */
    public function __invoke(RepriceProduct $command): void
    {
        $product = $this->repository->load(ProductId::fromString($command->id));
        $product->reprice(Money::fromCents($command->unitPriceInCents), $this->clock->now());

        $this->repository->save($product);
    }
}
