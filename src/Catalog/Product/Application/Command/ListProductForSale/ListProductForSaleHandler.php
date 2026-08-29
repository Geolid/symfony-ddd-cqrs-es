<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\ListProductForSale;

use Catalog\Product\Application\Exception\ProductLabelAlreadyTakenException;
use Catalog\Product\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class ListProductForSaleHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductLabelAlreadyTakenException
     * @throws ProductAlreadyExistsException
     */
    public function __invoke(ListProductForSale $command): void
    {
        $label = Label::fromString($command->label);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ProductUniqueKey::LABEL), $label->value, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw ProductLabelAlreadyTakenException::forLabel($label->value, $e);
        }

        $product = Product::list(
            id: ProductId::fromString($command->id),
            label: $label,
            unitAmount: Money::fromCents($command->unitAmountInCents),
            listedAt: $this->clock->now(),
        );

        $this->repository->save($product);
    }
}
