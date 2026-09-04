<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('catalog.listing.product.listed')]
final readonly class ProductListed
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitPriceInCents,
        public \DateTimeImmutable $listedAt,
    ) {
    }
}
