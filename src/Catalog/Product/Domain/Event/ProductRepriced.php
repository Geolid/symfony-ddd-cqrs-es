<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('catalog.product.product.repriced')]
final readonly class ProductRepriced
{
    public function __construct(
        public string $id,
        public int $unitAmountInCents,
        public string $repricedAt,
    ) {
    }
}
