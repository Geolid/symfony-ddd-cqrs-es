<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Processor;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Product\Domain\ValueObject\ProductUniqueValue;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\Processor;
use Shared\Domain\Service\UniqueValueRegistryInterface;

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

        $this->uniqueValues->release(ProductUniqueValue::LABEL, $product->label()->toString());
    }
}
