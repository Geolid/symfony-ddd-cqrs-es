<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\PublishProduct;

use Catalog\Listing\Application\Uniqueness\Exception\ProductLabelAlreadyInUseException;
use Catalog\Listing\Application\Uniqueness\ProductUniqueKey;
use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class PublishProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductLabelAlreadyInUseException
     * @throws ProductAlreadyExistsException
     */
    public function __invoke(PublishProduct $command): void
    {
        $label = Label::fromString($command->label);

        try {
            $this->uniqueValues->claim(UniqueKey::for(ProductUniqueKey::LABEL), $label->value, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw ProductLabelAlreadyInUseException::forLabel($label->value, $e);
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
