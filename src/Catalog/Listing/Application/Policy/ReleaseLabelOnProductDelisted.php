<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Policy;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\ValueObject\ProductUniqueKey;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[Policy('catalog.listing.release_label_on_product_delisted')]
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
