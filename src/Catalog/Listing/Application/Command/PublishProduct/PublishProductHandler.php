<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\PublishProduct;

use Catalog\Listing\Application\Command\PublishProduct\Exception\ProductLabelAlreadyTakenException;
use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Listing\Domain\ValueObject\ProductUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class PublishProductHandler
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
    public function __invoke(PublishProduct $command): void
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
            unitPrice: Money::fromCents($command->unitPriceInCents),
            listedAt: $this->clock->now(),
        );

        $this->repository->save($product);
    }
}
