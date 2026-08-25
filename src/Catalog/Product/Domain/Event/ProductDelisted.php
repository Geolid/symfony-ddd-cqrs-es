<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('catalog.product.product.delisted')]
final readonly class ProductDelisted
{
    public function __construct(
        public string $id,
        public string $delistedAt,
    ) {
    }
}
