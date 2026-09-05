<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Money;

#[Event('catalog.listing.product.repriced')]
final readonly class ProductRepriced
{
    public function __construct(
        public string $id,
        public Money $unitPrice,
        public \DateTimeImmutable $repricedAt,
    ) {
    }
}
