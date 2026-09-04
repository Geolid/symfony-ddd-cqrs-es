<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('catalog.listing.product.delisted')]
final readonly class ProductDelisted
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $delistedAt,
    ) {
    }
}
