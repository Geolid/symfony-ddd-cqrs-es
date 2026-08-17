<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Processor;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\Processor;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[Processor('catalog.product.release_label_on_product_delisted', sync: true)]
final readonly class ReleaseLabelOnProductDelisted
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws ProductNotFoundException
     */
    #[Subscribe(ProductDelisted::class)]
    public function __invoke(ProductDelisted $event): void
    {
        $product = $this->repository->load(ProductId::fromString($event->id));

        $this->uniqueValues->release(UniqueKey::for(ProductUniqueKey::LABEL), $product->label()->toString(), $product->id()->toString());
    }
}
