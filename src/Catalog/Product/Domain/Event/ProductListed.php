<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('catalog.product.product.listed')]
final readonly class ProductListed
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
        public string $listedAt,
    ) {
    }
}
