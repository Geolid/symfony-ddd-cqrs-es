<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Policy;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[Policy('catalog.product.release_label_on_product_delisted')]
final readonly class ReleaseLabelOnProductDelisted
{
    public function __construct(private UniqueValueRegistryInterface $uniqueValues)
    {
    }

    #[Subscribe(ProductDelisted::class)]
    public function __invoke(ProductDelisted $event): void
    {
        $this->uniqueValues->release(UniqueKey::for(ProductUniqueKey::LABEL), $event->id);
    }
}
